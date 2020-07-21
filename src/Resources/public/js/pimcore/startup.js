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
            text: t('valantic_dataquality_config_settings'),
            iconCls: 'pimcore_nav_icon_properties',
            handler: function () {
                try {
                    pimcore.globalmanager.get('valantic_dataquality_settings').activate({});
                } catch (e) {
                    pimcore.globalmanager.add('valantic_dataquality_settings', new valantic.dataquality.settings({}));
                }
            },
        });
    },

    postOpenObject: function (object) {
        Ext.Ajax.request({
            url: Routing.generate('valantic_dataquality_score_check'),
            method: 'get',
            params: {
                id: object.id,
            },
            success: function (response) {
                if (JSON.parse(response.responseText).status) {
                    object.tabbar.add(new valantic.dataquality.objectView(object).getLayout());
                    pimcore.layout.refresh();
                }
            },
        });
    },
});

// eslint-disable-next-line no-unused-vars
const ValanticDataQualityBundlePlugin = new pimcore.plugin.ValanticDataQualityBundle();
