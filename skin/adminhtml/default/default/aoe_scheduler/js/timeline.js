$.noConflict();

var Scheduler = Scheduler || {};

Scheduler.Schedule = function(data) {
    var self = this;
    self.schedule_id = data.schedule_id;
    self.status = data.status;
    self.start_time = new Date(data.start_time * 1000);
    self.duration = data.duration * 1000;
    self.style = {
        left: Scheduler.Timeline.pos(self.start_time),
        width: Scheduler.Timeline.width(self.duration, 3)
    };
};

Scheduler.Job = function(data) {
    var self = this;
    self.code = data.code;
    self.schedules = ko.observableArray();
    jQuery.each(data.schedules, function(index, item) {
        self.schedules.push(new Scheduler.Schedule(item));
    });
};

Scheduler.Timeline = function () {
    var self = this;

    this.jobs = ko.observableArray();
    this.hours = ko.observableArray();
    this.nowLinePos = ko.observable('0px');
    this.timelinePanelWidth = ko.observable('0px');
    this.showUI = ko.observable(false);

    this.start_time = null;

    this.autoRefreshInterval = null;

    this.collisionDetection = function (element) { collisionDetector.check(jQuery('.Timeline .task', element[1])); };

    this.refresh = function(afterCallback) {
        jQuery.getJSON(SCHEDULER_TIMELINE_DATA_URL, function (data) {
            self.start_time = new Date(data.start_time * 1000);

            self.jobs.removeAll();
            jQuery.each(data.jobs, function (index, item) {
                self.jobs.push(new Scheduler.Job(item));
            });

            self.hours(data.hours);
            self.nowLinePos(pos(new Date(data.now * 1000)));
            self.timelinePanelWidth(self.width(data.hours.length * 60 * 60 * 1000));
            self.showUI(true);

            if (afterCallback) {
                afterCallback();
            }
        });
    };

    this.toggleAutoRefresh = function() {
        var $button = jQuery('button.autorefresh');
        if ($button.hasClass('disabled')) {
            $button.removeClass('disabled').addClass('success');
            self.refresh();
            self.autoRefreshInterval = setInterval(self.refresh, 60 * 1000);
        } else {
            $button.removeClass('success').addClass('disabled');
            clearInterval(self.autoRefreshInterval);
        }
    };

    this.pos = function(date) {
        return self.width(date.getTime() - self.start_time.getTime(), 0);
    };

    /**
     * @param duration in milliseconds
     * @param minWidth in pixels
     * @returns {string}
     */
    this.width = function(duration, minWidth) {
        var width = Math.round(duration / 15000);
        if (minWidth) {
            width = Math.max(width, minWidth);
        }
        return width + 'px';
    };


    return this;
}();

jQuery(document).ready(function($) {
    ko.applyBindings(Scheduler.Timeline);
    Scheduler.Timeline.refresh(function() {
        // scroll all the way to the right when this is loaded the first time
        jQuery('.timeline-box').scrollLeft(jQuery('.timeline-panel').width());
    });
});
