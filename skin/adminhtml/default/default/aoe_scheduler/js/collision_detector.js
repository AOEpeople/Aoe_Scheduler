/**
 * Collision Detector
 *
 * @author Fabrizio Branca
 */
var collisionDetector = function () {
    var self = this;

    this.getPositions = function (box) {
        var $box = jQuery(box);
        var pos = $box.position();
        var width = $box.width();
        var height = $box.height();
        return [[pos.left, pos.left + width], [pos.top, pos.top + height]];
    };

    this.comparePositions = function (p1, p2) {
        var x1 = p1[0] < p2[0] ? p1 : p2;
        var x2 = p1[0] < p2[0] ? p2 : p1;
        return (x1[1] > x2[0] || x1[0] === x2[0]);
    };

    this.collision = function (a, b) {
        var posA = self.getPositions(a);
        var posB = self.getPositions(b);
        return (posA[1][0] == posB[1][0]) && self.comparePositions(posA[0], posB[0]);
    };

    this.check = function (items) {
        var numberOfItems = items.length;
        for (var i = 0; i < numberOfItems; i++) {
            var u = Math.min(i + 10, numberOfItems);
            for (var j = i + 1; j < u; j++) {
                if (self.collision(items[i], items[j])) {
                    var $subject = jQuery(items[i]);
                    var $object = jQuery(items[j]);
                    var objectTop = parseInt($subject.css('top'));
                    $object.css('top', (objectTop + 4) + 'px');
                    $subject.css('height', 18);
                    $object.css('height', 18);
                }
            }
        }
    };

    return this;
}();