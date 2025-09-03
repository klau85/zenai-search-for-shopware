import ListingPlugin from 'src/plugin/listing/listing.plugin'

export default class ZenaiListingPlugin extends ListingPlugin {
    init() {
        super.init();

        try {
            const searchParams = new URLSearchParams(window.location.search);
            const zenai = searchParams.get('zenai');
            if (zenai !== null && zenai !== undefined && zenai !== '') {
                this.options.params = Object.assign({}, this.options.params, { zenai });
            }
        } catch (e) {
            // no-op: if URLSearchParams not available or any error occurs
        }
    }
}
