import ZenaiSearchPlugin from "./zenai-search/zenai-search.plugin";
import ZenaiListingPlugin from "./zenai-listing/zenai-listing.decorator";

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('ZenaiSearchPlugin', ZenaiSearchPlugin, '[data-zenai-search-mode]');
PluginManager.override('Listing', () => import('./zenai-listing/zenai-listing.decorator'), '[data-listing]');