const { PluginBaseClass } = window;

export default class ZenaiSearchPlugin extends PluginBaseClass {
    init() {
        this.form = this.el.closest('form');
        if (!this.form) {
            return;
        }

        this.hiddenFieldName = 'zenai';
        this.hiddenField = this.form.querySelector(`input[name="${this.hiddenFieldName}"]`);
        this.storageKey = 'zenaiSearchPlugin.mode';
        this.defaultMode = this.el.value || 'ai';

        this.applyStoredMode();

        this.onModeChange = this.onModeChange.bind(this);
        this.onFormSubmit = this.onFormSubmit.bind(this);

        this.el.addEventListener('change', this.onModeChange);
        this.form.addEventListener('submit', this.onFormSubmit);

        this.syncHiddenField();
    }

    onModeChange() {
        this.persistMode();
        this.syncHiddenField();
    }

    onFormSubmit() {
        this.persistMode();
        this.syncHiddenField();
    }

    applyStoredMode() {
        const storedMode = this.loadMode();
        const modeToUse = this.hasOption(storedMode) ? storedMode : this.defaultMode;

        if (modeToUse && this.el.value !== modeToUse) {
            this.el.value = modeToUse;
        }
    }

    hasOption(value) {
        if (!value) {
            return false;
        }

        return Array.from(this.el.options).some((option) => option.value === value);
    }

    persistMode() {
        const mode = this.el.value || this.defaultMode;

        try {
            window.localStorage?.setItem(this.storageKey, mode);
        } catch (error) {
            // Ignore storage errors (e.g. disabled cookies)
        }
    }

    loadMode() {
        try {
            return window.localStorage?.getItem(this.storageKey);
        } catch (error) {
            return null;
        }
    }

    syncHiddenField() {
        if (!this.form) {
            return;
        }

        const isAiSearch = this.el.value === 'ai';

        if (isAiSearch) {
            if (!this.hiddenField) {
                this.hiddenField = document.createElement('input');
                this.hiddenField.type = 'hidden';
                this.hiddenField.name = this.hiddenFieldName;
                this.form.appendChild(this.hiddenField);
            }

            this.hiddenField.value = '1';

            return;
        }

        if (this.hiddenField) {
            this.hiddenField.remove();
            this.hiddenField = null;
        }
    }
}
