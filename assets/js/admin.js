// assets/js/admin.js
jQuery(document).ready(function($) {
    let recipientCount = $('.recipient-row').length;
    
    // Add new recipient row
    $('#add-recipient').on('click', function() {
        const newRow = $('<div>', {
            class: 'recipient-row'
        });
        
        newRow.append($('<input>', {
            type: 'text',
            name: `cf7_dynamic_recipients_settings[${recipientCount}][label]`,
            placeholder: 'Label',
            required: true
        }));
        
        newRow.append($('<input>', {
            type: 'email',
            name: `cf7_dynamic_recipients_settings[${recipientCount}][email]`,
            placeholder: 'Email',
            required: true
        }));
        
        newRow.append($('<button>', {
            type: 'button',
            class: 'button remove-recipient',
            text: 'Remove'
        }));
        
        $('#dynamic-recipients-container').append(newRow);
        recipientCount++;
    });
    
    // Remove recipient row
    $(document).on('click', '.remove-recipient', function() {
        $(this).closest('.recipient-row').remove();
        reindexRows();
    });
    
    // Reindex rows after removal
    function reindexRows() {
        $('.recipient-row').each(function(index) {
            $(this).find('input').each(function() {
                const name = $(this).attr('name');
                const newName = name.replace(/\[\d+\]/, `[${index}]`);
                $(this).attr('name', newName);
            });
        });
        recipientCount = $('.recipient-row').length;
    }
    
    // Form submission handling
    $('#dynamic-recipients-form').on('submit', function(e) {
        const form = $(this);
        const submitButton = form.find(':submit');
        
        // Basic validation
        let isValid = true;
        form.find('input[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        
        // Visual feedback
        form.addClass('loading');
        submitButton.prop('disabled', true);
        
        // Remove loading state after submission
        setTimeout(function() {
            form.removeClass('loading');
            submitButton.prop('disabled', false);
        }, 1000);
    });
});