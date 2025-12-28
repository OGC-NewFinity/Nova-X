jQuery(document).ready(function ($) {
    $('#nova_x_generate_theme').on('click', function () {
        const title = $('#nova_x_site_title').val();
        const prompt = $('#nova_x_prompt').val();

        $('#nova_x_response').text('Generating…');

        $.ajax({
            method: 'POST',
            url: NovaXData.restUrl,
            contentType: 'application/json',
            data: JSON.stringify({
                title,
                prompt,
                nonce: NovaXData.nonce,
            }),
            success: function (res) {
                if (res.success) {
                    $('#nova_x_response').text('✅ Theme generated: ' + (res.slug || 'success'));
                } else {
                    $('#nova_x_response').text('⚠️ ' + (res.message || 'Theme generation failed'));
                }
            },
            error: function (xhr) {
                $('#nova_x_response').text('❌ Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
            }
        });
    });
});

