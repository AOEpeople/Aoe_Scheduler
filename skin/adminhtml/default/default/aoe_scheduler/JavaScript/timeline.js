$.noConflict();
jQuery(document).ready(function($) {

    var milliSecondsPerPixel = 15000; // was "zoom"
    var start_time;

    function pos(date) {
        return width(date.getTime() - start_time.getTime(), 0);
    }

    /**
     * @param duration in milliseconds
     * @returns {string}
     */
    function width(duration, minWidth) {
        var width = Math.round(duration / milliSecondsPerPixel);
        if (minWidth) {
            width = Math.max(width, minWidth);
        }
        return width + 'px';
    }

    var Schedule = function(data) {
        var self = this;
        self.schedule_id = data.schedule_id;
        self.status = data.status;
        self.start_time = new Date(data.start_time * 1000);
        self.duration = data.duration * 1000;
        self.style = {
            left: pos(self.start_time),
            width: width(self.duration, 3)
        };
    };

    var Job = function(data) {
        var self = this;

        self.code = data.code;
        self.schedules = [];

        jQuery.each(data.schedules, function(index, item) {
            self.schedules.push(new Schedule(item));
            console.log("adding schedule");
        });
    };


    $.getJSON(SCHEDULER_TIMELINE_DATA_URL, function(data) {
        start_time = new Date(data.start_time * 1000);
        var jobs = [];
        jQuery.each(data.jobs, function(index, item) {
            jobs.push(new Job(item));
        });

        ko.applyBindings({
            jobs: jobs,
            hours: data.hours,
            timelinePanelWidth: width(data.hours.length * 60 * 60 * 1000),
            nowLinePos: pos(new Date(data.now * 1000))
        });
    });
});





function formatDate(date) {
    var normalizedDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0); // normalized date
    var day = new Date();
    var today = new Date(day.getFullYear(), day.getMonth(), day.getDate(), 0, 0, 0, 0);
    var tomorrow = new Date(day.getFullYear(), day.getMonth(), day.getDate()+1, 0, 0, 0, 0);
    var yesterday = new Date(day.getFullYear(), day.getMonth(), day.getDate()-1, 0, 0, 0, 0);
    var formattedDate = '';
    if (today.getTime() == normalizedDate.getTime()) {
    } else if (yesterday.getTime() == normalizedDate.getTime()) {
        formattedDate = 'Yesterday, ';
    } else if (tomorrow.getTime() == normalizedDate.getTime()) {
        formattedDate = 'Tomorrow, ';
    } else {
        formattedDate = normalizedDate.getFullYear() + '-' + normalizedDate.getMonth() + "-" + normalizedDate.getDate() + ', ';
    }
    if (date.getHours() < 10) {
        formattedDate = formattedDate + '0';
    }
    formattedDate = formattedDate + date.getHours() + ':';
    if (date.getHours() < 10) {
        formattedDate = formattedDate + '0';
    }
    if (date.getMinutes() < 10) {
        formattedDate = formattedDate + '0';
    }
    formattedDate = formattedDate + date.getMinutes();
    return formattedDate;
}