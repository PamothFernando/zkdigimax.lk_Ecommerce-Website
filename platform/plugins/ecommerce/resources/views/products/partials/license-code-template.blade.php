<script type="text/template" id="license-code-template">
    <tr data-license-code-id="__ID__">
        <td>
            <input type="text" 
                   name="license_codes[__ID__][code]" 
                   value="__CODE__" 
                   class="form-control license-code-input">
        </td>
        <td>
            {!! \Botble\Ecommerce\Enums\ProductLicenseCodeStatusEnum::AVAILABLE()->toHtml() !!}
        </td>
        <td>-</td>
        <td>
            <button type="button" 
                    class="btn btn-sm btn-danger license-code-delete-btn" 
                    data-license-code-id="__ID__">
                <i class="ti ti-trash"></i>
                {{ trans('core/base::tables.delete') }}
            </button>
        </td>
    </tr>
</script>

<!-- Generate License Codes Modal -->
<div class="modal fade" id="license-code-generate-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ trans('plugins/ecommerce::products.license_codes.generate_modal.title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ trans('plugins/ecommerce::products.license_codes.generate_modal.quantity') }}</label>
                    <input type="number" class="form-control" id="license-code-quantity" value="1" min="1" max="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ trans('plugins/ecommerce::products.license_codes.generate_modal.format') }}</label>
                    <select class="form-control" id="license-code-format">
                        <option value="uuid">UUID (e.g., 550e8400-e29b-41d4-a716-446655440000)</option>
                        <option value="alphanumeric">Alphanumeric (e.g., ABC123DEF456)</option>
                        <option value="numeric">Numeric (e.g., 123456789012)</option>
                        <option value="custom">Custom Pattern</option>
                    </select>
                </div>
                <div class="mb-3" id="custom-pattern-group" style="display: none;">
                    <label class="form-label">{{ trans('plugins/ecommerce::products.license_codes.generate_modal.custom_pattern') }}</label>
                    <input type="text" class="form-control" id="license-code-pattern" placeholder="e.g., PROD-####-####">
                    <small class="form-text text-muted">
                        {{ trans('plugins/ecommerce::products.license_codes.generate_modal.pattern_help') }}
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ trans('core/base::forms.cancel') }}
                </button>
                <button type="button" class="btn btn-primary" id="generate-license-codes-btn">
                    {{ trans('plugins/ecommerce::products.license_codes.generate_modal.generate') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let licenseCodeCounter = {{ $product ? $product->licenseCodes->count() : 0 }};

        // Handle license code type change
        $('#license_code_type').on('change', function() {
            const selectedType = $(this).val();
            const licenseCodesManagement = $('#license-codes-management');

            if (selectedType === 'pick_from_list') {
                licenseCodesManagement.show();
            } else {
                licenseCodesManagement.hide();
            }
        });

        // Handle generate license code checkbox change
        $('input[name="generate_license_code"]').on('change', function() {
            const isChecked = $(this).is(':checked');
            const licenseCodeOptions = $('#license-code-options');
            const licenseCodesManagement = $('#license-codes-management');

            if (isChecked) {
                licenseCodeOptions.addClass('show');
                // Check if pick_from_list is selected
                if ($('#license_code_type').val() === 'pick_from_list') {
                    licenseCodesManagement.show();
                }
            } else {
                licenseCodeOptions.removeClass('show');
                licenseCodesManagement.hide();
            }
        });

        // Add license code
        $(document).on('click', '.license-code-add-btn', function() {
            const template = $('#license-code-template').html();
            const newId = 'new_' + Date.now();
            const newRow = template
                .replace(/__ID__/g, newId)
                .replace(/__CODE__/g, '');
            
            $('#license-codes-table-body').append(newRow);
            licenseCodeCounter++;
        });

        // Delete license code
        $(document).on('click', '.license-code-delete-btn', function() {
            const licenseCodeId = $(this).data('license-code-id');
            const row = $(this).closest('tr');
            
            if (licenseCodeId.toString().startsWith('new_')) {
                // New license code, just remove the row
                row.remove();
            } else {
                // Existing license code, mark for deletion
                row.append('<input type="hidden" name="license_codes[' + licenseCodeId + '][_delete]" value="1">');
                row.hide();
            }
        });

        // Generate license codes
        $(document).on('click', '.license-code-generate-btn', function() {
            const modal = $('#license-code-generate-modal');
            if (modal.length) {
                modal.modal('show');
            }
        });

        // Handle format change
        $(document).on('change', '#license-code-format', function() {
            if ($(this).val() === 'custom') {
                $('#custom-pattern-group').show();
            } else {
                $('#custom-pattern-group').hide();
            }
        });

        // Generate codes
        $(document).on('click', '#generate-license-codes-btn', function() {
            const quantity = parseInt($('#license-code-quantity').val());
            const format = $('#license-code-format').val();
            const pattern = $('#license-code-pattern').val();
            
            for (let i = 0; i < quantity; i++) {
                const code = generateLicenseCode(format, pattern);
                const template = $('#license-code-template').html();
                const newId = 'new_' + Date.now() + '_' + i;
                const newRow = template
                    .replace(/__ID__/g, newId)
                    .replace(/__CODE__/g, code);
                
                $('#license-codes-table-body').append(newRow);
            }
            
            const modal = $('#license-code-generate-modal');
            if (modal.length) {
                modal.modal('hide');
            }
        });

        function generateLicenseCode(format, pattern) {
            switch (format) {
                case 'uuid':
                    return generateUUID();
                case 'alphanumeric':
                    return generateAlphanumeric(12);
                case 'numeric':
                    return generateNumeric(12);
                case 'custom':
                    return generateCustomPattern(pattern);
                default:
                    return generateUUID();
            }
        }

        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        function generateAlphanumeric(length) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        function generateNumeric(length) {
            let result = '';
            for (let i = 0; i < length; i++) {
                result += Math.floor(Math.random() * 10);
            }
            return result;
        }

        function generateCustomPattern(pattern) {
            return pattern.replace(/#/g, () => Math.floor(Math.random() * 10))
                         .replace(/A/g, () => String.fromCharCode(65 + Math.floor(Math.random() * 26)))
                         .replace(/a/g, () => String.fromCharCode(97 + Math.floor(Math.random() * 26)));
        }
    });
</script>
