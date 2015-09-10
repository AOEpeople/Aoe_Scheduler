#!/bin/bash

# Exit when any command returns an error status
set -o errexit

# Generate an error if any variable doesn't exist
set -o nounset

# Location of the php binary
PHP_BIN=$(which php || true)
if [ -z "${PHP_BIN}" ]; then
    echo "Could not find a binary for php" 1>&2
    exit 1
fi

# Location of the md5sum binary
MD5SUM_BIN=$(which md5sum || true)
if [ -z "${MD5SUM_BIN}" ]; then
    echo "Could not find a binary for md5sum" 1>&2
    exit 1
fi

# Absolute path to Magento installation shell scripts
DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/shell" && pwd)
if [[ -z "${DIR}" || ! -d "${DIR}" ]]; then
    echo "Could not resolve base shell directory" 1>&2
    exit 1
fi

# The scheduler.php script
SCHEDULER="scheduler.php"
if [[ ! -e "${DIR}/${SCHEDULER}" || ! -r "${DIR}/${SCHEDULER}" ]]; then
    echo "Could not find scheduler.php script" 1>&2
    exit 1
fi

# Defaults
MODE="default"
INCLUDE_GROUPS=""
EXCLUDE_GROUPS=""
INCLUDE_JOBS=""
EXCLUDE_JOBS=""
ACTION=""
WORKERS=10
WAIT=1

# Parse command line args (very simplistic)
while [ $# -gt 0 ]; do
    case "$1" in
        --mode)
            MODE=$2
            shift 2
        ;;
        --includeGroups)
            INCLUDE_GROUPS=$2
            shift 2
        ;;
        --excludeGroups)
            EXCLUDE_GROUPS=$2
            shift 2
        ;;
        --includeJobs)
            INCLUDE_JOBS=$2
            shift 2
        ;;
        --excludeJobs)
            EXCLUDE_JOBS=$2
            shift 2
        ;;
        --action)
            ACTION=$2
            shift 2
        ;;
        --workers)
            WORKERS=$2
            shift 2
        ;;
        --wait)
            WAIT=$2
            shift 2
        ;;
        --)
            shift
            break
        ;;
        *)
            echo "Invalid arguments." >&2
            exit 1
        ;;
    esac
done

# Verify we have a MODE parameter
if [ -z "${MODE}" ]; then
    echo "Cron run mode MUST be defined." 1>&2
    exit 1
fi

if [ "${MODE}" = "daemonize" ]; then
    cd "${DIR}"
    while true; do
        # Count the number of running jobs right now
        RUNNING_WORKERS=`ps aux | grep "${SCHEDULER} --action daemonRun" | grep -v grep | wc -l`
        # Only fetch the next job if there are enough available workers
        if [ "${RUNNING_WORKERS}" -lt ${WORKERS} ]; then
            # Fetches the next schedule --code and --id to run
            NEXT_JOB=`${PHP_BIN} ${SCHEDULER} --action daemonNext`
            if [ ! -z "${NEXT_JOB}" ]; then
                # Strip the --id param so we can search for jobs with the same code
                NEXT_WITHOUT_ID=`echo $NEXT_JOB | sed -r 's/--id [0-9]+//g'`
                # Only run this schedule now if a worker is not running a job with the same code
                if ! ps auxwww | grep "${SCHEDULER} --action daemonRun ${NEXT_WITHOUT_ID}" | grep -v grep 1>/dev/null 2>/dev/null ; then
                    "${PHP_BIN}" "${SCHEDULER}" --action runScheduleById ${NEXT_JOB} &
                else
                    # Otherwise mark it as missed
                    "${PHP_BIN}" "${SCHEDULER}" --action daemonMultiple ${NEXT_JOB} &
                fi
            fi
        else
            sleep "${WAIT}"
        fi
    done
fi

function getDaemonPid {
    DAEMON_PID=`ps aux | grep "$0 --mode daemonize" | grep -v grep | awk '{print $2}'`
}

if [ "${MODE}" = "daemon" ]; then
    getDaemonPid
    case "${ACTION}" in
        start)
            if [ ! -z "${DAEMON_PID}" ]; then
                echo "Daemon is already running"
            else
                $0 --mode daemonize &
                getDaemonPid
                echo "Daemon started, PID ${DAEMON_PID}"
            fi
        ;;
        stop)
            if [ -z "${DAEMON_PID}" ]; then
                echo "Daemon is not running"
            else
                kill "${DAEMON_PID}"
                getDaemonPid
                if [ -z "${DAEMON_PID}" ]; then
                    echo "Daemon stopped"
                else
                    echo "Failed to stop daemon"
                fi
            fi
        ;;
        status)
            if [ ! -z "${DAEMON_PID}" ]; then
                echo "Daemon is running, PID ${DAEMON_PID}"
            else
                echo "Daemon is not running"
            fi
        ;;
        *)
            echo "Usage: ./scheduler_cron.sh --mode daemon --action (start|stop|status)"
        ;;
    esac
    exit 0
fi

# Unique identifier for this cron job run
IDENTIFIER=$(echo -n "${DIR}|${MODE}|${INCLUDE_GROUPS}|${EXCLUDE_GROUPS}|${INCLUDE_JOBS}|${EXCLUDE_JOBS}" | "${MD5SUM_BIN}" - | cut -f1 -d' ')

# Lock process to one run per set of options (This REQUIRES 'set -e' or 'set -o errexit')
# This is to prevent multiple processes for the same cron parameters (And the only reason we don't call PHP directly)
LOCK="/tmp/magento.aoe_scheduler.${IDENTIFIER}.lock"
mkdir "${LOCK}" >/dev/null 2>&1
# TODO: add pid writing and check
trap 'rmdir "${LOCK}"; exit $?' INT TERM EXIT

# Needed because PHP is braindead and resolves symlinks before setting __FILE__
cd "${DIR}"

# Build the options
OPTIONS=""
if [ -n "${INCLUDE_GROUPS}" ]; then
    OPTIONS="${OPTIONS} --includeGroups ${INCLUDE_GROUPS}"
fi
if [ -n "${EXCLUDE_GROUPS}" ]; then
    OPTIONS="${OPTIONS} --excludeGroups ${EXCLUDE_GROUPS}"
fi
if [ -n "${INCLUDE_JOBS}" ]; then
    OPTIONS="${OPTIONS} --includeJobs ${INCLUDE_JOBS}"
fi
if [ -n "${EXCLUDE_JOBS}" ]; then
    OPTIONS="${OPTIONS} --excludeJobs ${EXCLUDE_JOBS}"
fi

# Run the job in the foreground
"${PHP_BIN}" "${SCHEDULER}" --action cron --mode ${MODE} ${OPTIONS}
