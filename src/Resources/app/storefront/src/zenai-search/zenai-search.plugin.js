const { PluginBaseClass } = window;

export default class ZenaiSearchPlugin extends PluginBaseClass {
    init() {
        this.el.addEventListener('click', this.onClick.bind(this));
    }

    onClick(event) {
        const form = this.el.closest('form');
        if (!form) {
            return;
        }

        // Mark the request so our backend decorator can detect it
        let hidden = form.querySelector('input[name="zenai"]');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'zenai';
            form.appendChild(hidden);
        }
        hidden.value = '1';

        // Submit the form (will go to frontend.search.page)
        form.submit();
    }
}