document.addEventListener('DOMContentLoaded', function () {
    var countdownNodes = document.querySelectorAll('[data-reset-countdown]');

    function formatDuration(totalSeconds) {
        var seconds = Math.max(0, parseInt(totalSeconds, 10) || 0);
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var remainingSeconds = seconds % 60;

        if (hours > 0) {
            return [
                String(hours).padStart(2, '0'),
                String(minutes).padStart(2, '0'),
                String(remainingSeconds).padStart(2, '0')
            ].join(':');
        }

        return [
            String(minutes).padStart(2, '0'),
            String(remainingSeconds).padStart(2, '0')
        ].join(':');
    }

    countdownNodes.forEach(function (node) {
        var remaining = parseInt(node.getAttribute('data-reset-countdown'), 10) || 0;
        var prefix = node.getAttribute('data-prefix') || '';
        var expiredText = node.getAttribute('data-expired-text') || 'Expired.';

        function render() {
            if (remaining <= 0) {
                node.textContent = expiredText;
                return;
            }

            node.textContent = prefix + formatDuration(remaining);
            remaining -= 1;
            window.setTimeout(render, 1000);
        }

        render();
    });
});
