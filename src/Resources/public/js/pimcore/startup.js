pimcore.registerNS('pimcore.plugin.ValanticDataQualityBundle');

pimcore.plugin.ValanticDataQualityBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return 'pimcore.plugin.ValanticDataQualityBundle';
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    // eslint-disable-next-line no-unused-vars
    pimcoreReady: function (params, broker) {
        const menu = pimcore.globalmanager.get('layout_toolbar').marketingMenu;
        menu.add({
            text: t('valantic_dataquality_pimcore_nav_menu_name'),
            iconCls: 'pimcore_nav_icon_object',
            handler: function () {
                try {
                    pimcore.globalmanager.get('valantic_dataquality_editor').activate();
                } catch (e) {
                    pimcore.globalmanager.add('valantic_dataquality_editor', new valantic.dataquality.editor());
                }
            },
        });
    },

    postOpenObject: function (object) {
        object.tabbar.add(new valantic.dataquality.object_view(object).getLayout());
        pimcore.layout.refresh();
    },
});

// eslint-disable-next-line no-unused-vars
var ValanticDataQualityBundlePlugin = new pimcore.plugin.ValanticDataQualityBundle();
