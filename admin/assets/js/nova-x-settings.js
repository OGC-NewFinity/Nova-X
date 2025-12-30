/**
 * Nova-X Settings Page JavaScript
 * 
 * Handles field change detection and prevents accidental overwrites.
 * 
 * @package Nova-X
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#nova-x-settings-form');
        const $keyFields = $('.nova-x-api-key-field');

        if (!$form.length || !$keyFields.length) {
            return;
        }

        /**
         * Check if a value is a masked key (contains bullet points or asterisks)
         */
        function isMaskedValue(value) {
            return /[•*]/.test(value);
        }

        /**
         * Update field status based on current value
         */
        function updateFieldStatus($field) {
            const currentValue = $field.val().trim();
            const originalValue = $field.data('original-value') || '';
            const provider = $field.data('provider');

            // Remove existing status classes
            $field.removeAttr('data-status');

            if (currentValue === originalValue && originalValue !== '') {
                // Unchanged - field contains original masked value
                $field.attr('data-status', 'unchanged');
            } else if (currentValue === '') {
                // Empty - user wants to delete
                $field.attr('data-status', 'empty');
            } else if (isMaskedValue(currentValue)) {
                // User entered masked characters - treat as unchanged
                $field.attr('data-status', 'unchanged');
            } else if (currentValue !== originalValue) {
                // Changed - new value entered
                $field.attr('data-status', 'changed');
            }
        }

        /**
         * Handle field input events
         */
        $keyFields.on('input change paste', function() {
            const $field = $(this);
            
            // Small delay to allow paste to complete
            setTimeout(function() {
                updateFieldStatus($field);
            }, 100);
        });

        /**
         * Handle form submission - prevent accidental overwrites
         */
        $form.on('submit', function(e) {
            let hasChanges = false;
            let hasErrors = false;

            $keyFields.each(function() {
                const $field = $(this);
                const currentValue = $field.val().trim();
                const originalValue = $field.data('original-value') || '';
                const status = $field.attr('data-status');

                // If field contains masked value and wasn't changed, clear it before submit
                // This prevents sending masked values to the server
                if (status === 'unchanged' && isMaskedValue(currentValue)) {
                    // Restore original masked value (server will ignore it)
                    $field.val(originalValue);
                } else if (status === 'changed' || status === 'empty') {
                    hasChanges = true;
                }

                // Validate format on client side (basic check)
                if (currentValue && !isMaskedValue(currentValue)) {
                    // Basic length check
                    if (currentValue.length < 10) {
                        hasErrors = true;
                        $field.css('border-color', '#ff4d4f');
                        alert('API key appears too short. Please check the format.');
                        e.preventDefault();
                        return false;
                    }
                }
            });

            // Show confirmation if deleting keys
            if (!hasChanges) {
                // No changes detected - form will submit but server will ignore
                return true;
            }

            // Check for empty fields (deletions)
            let deletionCount = 0;
            $keyFields.each(function() {
                const $field = $(this);
                const status = $field.attr('data-status');
                const originalValue = $field.data('original-value') || '';
                
                if (status === 'empty' && originalValue !== '') {
                    deletionCount++;
                }
            });

            if (deletionCount > 0) {
                const confirmMessage = deletionCount === 1 
                    ? 'You are about to delete an API key. This action cannot be undone. Continue?'
                    : `You are about to delete ${deletionCount} API keys. This action cannot be undone. Continue?`;
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            }

            return true;
        });

        /**
         * Initialize field statuses on page load
         */
        $keyFields.each(function() {
            updateFieldStatus($(this));
        });

        /**
         * Handle provider dropdown change (if exists)
         * Update field visibility or status
         */
        $('#nova_x_selected_provider').on('change', function() {
            // If using single provider selector, update field visibility
            // This is for backward compatibility
            const selectedProvider = $(this).val().toLowerCase();
            $keyFields.each(function() {
                const $field = $(this);
                const fieldProvider = $field.data('provider');
                
                // Show/hide based on selection (if single provider mode)
                // For now, all fields are always visible
            });
        });

        /**
         * Rotate token for a provider
         * 
         * @param {string} provider Provider slug
         * @param {string} newKey New API key (optional, can be empty to rotate existing)
         * @returns {Promise} Promise that resolves with response data
         */
        function rotateToken(provider, newKey = '') {
            if (!NovaXData || !NovaXData.rotate_token_url || !NovaXData.nonce) {
                return Promise.reject(new Error('Configuration error. Please refresh the page.'));
            }

            return fetch(NovaXData.rotate_token_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': NovaXData.nonce
                },
                body: JSON.stringify({
                    provider: provider,
                    new_key: newKey.trim(),
                    force: true
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `Request failed with status ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Token rotation failed');
                }
                return data;
            });
        }

        /**
         * Handle rotate token button clicks (if buttons exist)
         */
        $(document).on('click', '.rotate-token-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const provider = $btn.data('provider') || $btn.attr('data-provider');
            
            if (!provider) {
                alert('⚠️ Provider not specified.');
                return;
            }

            // Get the API key from the corresponding field
            const $keyField = $(`input[data-provider="${provider}"]`);
            const $row = $keyField.closest('tr');
            const $status = $row.find('.rotate-token-status');
            const newKey = $keyField.length ? $keyField.val().trim() : '';

            // Validate that a key is provided
            if (!newKey) {
                alert('⚠️ Please enter an API key in the field before rotating.');
                $keyField.focus();
                return;
            }

            // Check if key is masked (should not be used)
            if (isMaskedValue(newKey)) {
                alert('⚠️ Please enter a valid, unmasked API key. Masked values cannot be used for rotation.');
                $keyField.focus();
                return;
            }

            // Basic validation - check length
            if (newKey.length < 10) {
                alert('⚠️ API key appears too short. Please check the format.');
                $keyField.focus();
                return;
            }

            if (!confirm(`Are you sure you want to rotate the token for ${provider}? This will replace the existing encrypted token.`)) {
                return;
            }

            // Disable button and show loading
            $btn.prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('🔄 Rotating...');
            
            if ($status.length) {
                $status.html('🔄 Rotating token...').removeClass('error success').addClass('rotating');
            }

            rotateToken(provider, newKey)
                .then(data => {
                    if ($status.length) {
                        $status.html('✅ ' + (data.message || 'Token rotated successfully'));
                        $status.removeClass('error rotating').addClass('success');
                    }
                    
                    // Show success message
                    const successMsg = (data.notifier && data.notifier.message) 
                        ? data.notifier.message 
                        : (data.message || 'Token rotated successfully');
                    
                    // Update field with new masked value if provided
                    if (data.masked_key && $keyField.length) {
                        $keyField.val(data.masked_key);
                        $keyField.data('original-value', data.masked_key);
                        updateFieldStatus($keyField);
                    } else if ($keyField.length && newKey) {
                        // Clear the key field after successful rotation
                        $keyField.val('');
                    }

                    // Reload page after delay to refresh settings
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                })
                .catch(error => {
                    const errorMsg = error.message || 'Token rotation failed';
                    
                    if ($status.length) {
                        $status.html('❌ ' + errorMsg);
                        $status.removeClass('success rotating').addClass('error');
                    }
                    
                    alert('⚠️ ' + errorMsg);
                })
                .finally(() => {
                    $btn.prop('disabled', false);
                    $btn.html('🔄 Rotate Token');
                });
        });

        /**
         * Generic error handler for failed requests
         */
        function handleRequestError(error, defaultMessage = 'Request failed') {
            const message = error.message || defaultMessage;
            console.error('[Nova-X Settings]', message, error);
            
            // Show user-friendly error
            alert('⚠️ ' + message);
            
            return {
                success: false,
                message: message
            };
        }

        // Expose rotateToken function globally for potential external use
        if (typeof window !== 'undefined') {
            window.novaXSettings = {
                rotateToken: rotateToken,
                handleError: handleRequestError
            };
        }
    });

})(jQuery);

