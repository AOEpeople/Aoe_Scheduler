$.noConflict();
jQuery(function() {
    function updateInstructions() {
        jQuery('.croncommand').hide();
        jQuery('.configuration input').removeAttr("disabled");
        if (!jQuery('.configuration input[name="scheduler-cron"]').is(':checked')) {
            jQuery('.classic').show();
            jQuery('.configuration input[name="use-crongroups"]').attr('checked', false).attr("disabled", true);
        }
        if (jQuery('.configuration input[name="use-crongroups"]').is(':checked')) {
            jQuery('.configuration input[name="scheduler-cron"]').attr('checked', true).attr("disabled", true);
            jQuery('.crongroups').show();
        } else if (jQuery('.configuration input[name="scheduler-cron"]').is(':checked')) {
            jQuery('.scheduler').show();
        }
        jQuery('.every-five-minutes').toggle(!jQuery('.configuration input[name="every-minute"]').is(':checked'));
        jQuery('.maintenance-check-command').toggle(jQuery('.configuration input[name="maintenance-check"]').is(':checked'));
        jQuery('.watchdog').toggle(jQuery('.configuration input[name="use-watchdog"]').is(':checked'));
        jQuery('.mailto').toggle(jQuery('.configuration input[name="add-mailto"]').is(':checked'));

    }
    updateInstructions();
    jQuery('.configuration input').change(updateInstructions);
});