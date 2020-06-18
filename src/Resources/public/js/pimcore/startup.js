pimcore.registerNS("pimcore.plugin.ValanticDataQualityBundle");

pimcore.plugin.ValanticDataQualityBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.ValanticDataQualityBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        const menu = pimcore.globalmanager.get("layout_toolbar").marketingMenu;
        menu.add({
            text: t("valantic_dataquality_pimcore_nav_menu_name"),
            iconCls: "pimcore_nav_icon_object",
            handler: function () {
                try {
                    pimcore.globalmanager.get("valantic_dataquality_editor").activate();
                }
                catch (e) {
                    pimcore.globalmanager.add("valantic_dataquality_editor", new valantic.dataquality.editor());
                }
            }
        });
    },

    postOpenObject:function(object){
        object.tabbar.add({
            title: t('valantic_dataquality_pimcore_tab_name'),
            tabConfig: {
                tooltip: t('valantic_dataquality_pimcore_tab_name')
            },
            iconCls: 'pimcore_material_icon_info pimcore_material_icon',
            handler: function (obj) {
                // TODO: vendor/pimcore/pimcore/bundles/AdminBundle/Resources/public/js/pimcore/object/object.js
                // TODO: vendor/pimcore/pimcore/bundles/AdminBundle/Resources/public/js/pimcore/object/versions.js
            }.bind(this, object)
        });
        pimcore.layout.refresh();
    }
});

var ValanticDataQualityBundlePlugin = new pimcore.plugin.ValanticDataQualityBundle();
