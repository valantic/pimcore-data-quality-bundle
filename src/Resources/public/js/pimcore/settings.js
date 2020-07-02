pimcore.registerNS('valantic.dataquality.settings');
valantic.dataquality.settings = Class.create({

    initialize: function () {
        if (!this.panel) {
            const settingsContainerItems = [];

            const configMeta = new valantic.dataquality.settings_meta(this);
            const configConstraints = new valantic.dataquality.settings_constraints(this);

            settingsContainerItems.push(configConstraints.getLayout());
            settingsContainerItems.push(configMeta.getLayout());

            this.settingsContainer = new Ext.TabPanel({
                activeTab: 0,
                deferredRender: false,
                enableTabScroll: true,
                items: settingsContainerItems,
            });

            this.panel = new Ext.Panel({
                id: 'valantic_dataquality_settings',
                title: t('valantic_dataquality_config_settings'),
                iconCls: 'pimcore_nav_icon_properties',
                border: false,
                layout: 'fit',
                closable: true,
                items: [this.settingsContainer],
            });

            const tabPanel = Ext.getCmp('pimcore_panel_tabs');
            tabPanel.add(this.panel);
            tabPanel.setActiveItem('valantic_dataquality_settings');

            this.panel.on('destroy', function () {
                pimcore.globalmanager.remove('reports_settings');
            });

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    activate: function () {
        const tabPanel = Ext.getCmp('pimcore_panel_tabs');
        tabPanel.setActiveItem('valantic_dataquality_settings');
    },

});
