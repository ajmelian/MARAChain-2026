/**
 * MARAChain Form Validation v1.4.0
 *
 * Client-side validation matching Config\Validation.php backend rules.
 * Uses native HTML5 Constraint Validation API with custom regex patterns.
 * No jQuery dependency — pure ES6+.
 *
 * @package MARAChain\Assets\JS
 * @author  Aythami
 * @since   1.4.0
 */
const MARAValidation = {
    /**
     * Regex patterns that mirror backend validation rules.
     *
     * @type {Object<string, RegExp>}
     */
    patterns: {
        email:      /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
        phone:      /^\+?[1-9]\d{1,14}$/,
        postalCode: /^\d{5}$/,
        firstName:  /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]{1,100}$/,
        legalName:  /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s\.\,\-]{1,200}$/,
        hex64:      /^[0-9a-fA-F]{64}$/,
        uuid:       /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i,
    },

    /**
     * User-facing error messages in Spanish.
     *
     * @type {Object<string, string>}
     */
    messages: {
        email:      'Introduce un email válido.',
        phone:      'Introduce un teléfono válido (ej: +34600123456).',
        postalCode: 'El código postal debe tener 5 dígitos.',
        firstName:  'Solo letras y espacios (1-100 caracteres).',
        legalName:  'Solo letras, números, espacios y puntuación básica.',
        required:   'Este campo es obligatorio.',
        hex64:      'Debe ser un hash SHA-256 válido (64 caracteres hexadecimales).',
    },

    /**
     * Validate a single form field.
     *
     * Checks the `required` attribute and matches the value against
     * the pattern registered for `field.name`.
     *
     * @param {HTMLElement} field The form field element.
     * @param {string}      value The field value to validate.
     * @returns {string|null} Error message or null if valid.
     *
     * @since 1.4.0
     */
    validate(field, value) {
        if (!value && field.required) {
            return this.messages.required;
        }
        if (this.patterns[field.name] && value) {
            if (!this.patterns[field.name].test(value)) {
                return this.messages[field.name] || 'Formato inválido.';
            }
        }
        return null;
    },

    /**
     * Validate all required and pattern-constrained fields in a form.
     *
     * Adds/removes `is-invalid` and `is-valid` CSS classes on each field
     * and sets the `.invalid-feedback` text content.
     *
     * @param {HTMLFormElement} form The form element to validate.
     * @returns {boolean} True if all fields pass validation.
     *
     * @since 1.4.0
     */
    validateForm(form) {
        const fields = form.querySelectorAll(
            'input[required], input[pattern], select[required], textarea[required]'
        );
        let valid = true;

        fields.forEach(field => {
            const error = this.validate(field, field.value);
            const feedback = field.parentElement.querySelector('.invalid-feedback');
            if (error) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
                if (feedback) {
                    feedback.textContent = error;
                }
                valid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });

        return valid;
    },

    /**
     * Initialize validation on all forms with `.needs-validation` class.
     *
     * Attaches a submit event listener that runs Bootstrap 5-style
     * validation (checkValidity + custom validateForm).
     *
     * @since 1.4.0
     */
    init() {
        document.querySelectorAll('.needs-validation').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity() || !this.validateForm(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }
};
