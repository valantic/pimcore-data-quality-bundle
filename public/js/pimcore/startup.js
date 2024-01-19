document.addEventListener(pimcore.events.pimcoreReady, () => {
    if (!pimcore.globalmanager.get('user').isAllowed('plugin_valantic_dataquality_config')) {
        return;
    }

    if (layoutToolbar.settingsMenu) {
        layoutToolbar.settingsMenu.add(
            new Ext.Action({
                id: 'valantic_dataquality_config_settings',
                text: t('valantic_dataquality_config_settings'),
                iconCls: 'pimcore_nav_icon_properties',
                handler: function () {
                    try {
                        pimcore.globalmanager.get('valantic_dataquality_settings').activate();
                    } catch (e) {
                        pimcore.globalmanager.add('valantic_dataquality_settings', new valantic.dataquality.settings({}));
                    }
                },
            })
        );
    }
});

document.addEventListener(pimcore.events.postOpenObject, (event) => {
    if (!pimcore.globalmanager.get('user').isAllowed('plugin_valantic_dataquality_config')) {
        return;
    }

    Ext.Ajax.request({
        url: Routing.generate('valantic_dataquality_score_check'),
        method: 'get',
        disableCaching: false,
        params: {
            id: event.detail.object.id,
        },
        success: (response) => {
            if (JSON.parse(response.responseText).status) {
                const tab = new valantic.dataquality.objectView(event.detail.object);
                const objectTabPanel = event.detail.object.tab.items.items[1];

                objectTabPanel.insert(objectTabPanel.items.length, tab.getLayout());
                objectTabPanel.updateLayout();

                pimcore.layout.refresh();
            }
        },
    });
});
