import ZenaiSearchPlugin from "./zenai-search/zenai-search.plugin";

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('ZenaiSearchPlugin', ZenaiSearchPlugin, '[data-zenai-search-btn]');