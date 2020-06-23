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
            text: t('valantic_dataquality_config_constraints_tooltip'),
            iconCls: 'pimcore_nav_icon_object',
            handler: function () {
                try {
                    pimcore.globalmanager.get('valantic_dataquality_constraints').activate();
                } catch (e) {
                    pimcore.globalmanager.add('valantic_dataquality_constraints', new valantic.dataquality.constraints());
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
const ValanticDataQualityBundlePlugin = new pimcore.plugin.ValanticDataQualityBundle();
