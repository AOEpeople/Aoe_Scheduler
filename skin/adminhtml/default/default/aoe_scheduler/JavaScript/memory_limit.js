document.observe('dom:loaded', function () {
    $$('#job_grid_table select[name="memory_limit"]').invoke('observe', 'change', function (event) {
        var job_code = this.up('tr').down('.job_code').textContent.trim();
        var memory_limit = this.value;
        new Ajax.Request(
            BASE_ADMIN_URL + 'job/changeMemory',
            {
                method: 'post',
                parameters: {job_code: job_code, memory_limit: memory_limit},
                onSuccess: function (transport) {
                    var json = transport.responseText.evalJSON() || '{}';
                    if (!json.success) {
                        alert('error saving memory limit, please check your logs');
                    }
                }
            }
        );
    });
});