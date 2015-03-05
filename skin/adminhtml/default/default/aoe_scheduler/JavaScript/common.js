$.noConflict();
jQuery(function () {
    jQuery('.timeline-box').scrollLeft(jQuery('.timeline-panel').width());

    jQuery('.task').tooltip({
        offsetParent: 'body',
        predelay: 100,
        position: 'bottom center',
        onShow: function () {
            this.getTrigger().addClass('active');
        },
        onHide: function () {
            this.getTrigger().removeClass('active');
        }
    }).dynamic();

    // collision detection
    function getPositions(box) {
        var $box = jQuery(box);
        var pos = $box.position();
        var width = $box.width();
        var height = $box.height();
        return [[pos.left, pos.left + width], [pos.top, pos.top + height]];
    }

    function comparePositions(p1, p2) {
        var x1 = p1[0] < p2[0] ? p1 : p2;
        var x2 = p1[0] < p2[0] ? p2 : p1;
        return (x1[1] > x2[0] || x1[0] === x2[0]);
    }

    function collision(a, b) {
        var posA = getPositions(a);
        var posB = getPositions(b);

        return (posA[1][0] == posB[1][0]) && comparePositions(posA[0], posB[0]);
    }

    jQuery('.timeline').each(function () {
        var $tasks = jQuery('.task', jQuery(this));
        var numberOfTasks = $tasks.length;
        for (var i = 0; i < numberOfTasks; i++) {
            var u = Math.min(i + 10, numberOfTasks);
            for (var j = i + 1; j < u; j++) {
                if (collision($tasks[i], $tasks[j])) {
                    var $subject = jQuery($tasks[i]);
                    var $object = jQuery($tasks[j]);

                    var objectTop = parseInt($subject.css('top'));

                    $object.css('top', (objectTop + 4) + 'px');

                    $subject.css('height', 18);
                    $object.css('height', 18);
                }
            }
        }
    });
});
