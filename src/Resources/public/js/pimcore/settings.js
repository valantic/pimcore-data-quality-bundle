pimcore.registerNS('valantic.dataquality.settings');
valantic.dataquality.settings = Class.create({

    initialize: function (payload) {
        if (!this.panel) {
            const settingsContainerItems = [];

            this.configMeta = new valantic.dataquality.settings_meta(this);
            this.configConstraints = new valantic.dataquality.settings_constraints(this);

            settingsContainerItems.push(this.configConstraints.getLayout());
            settingsContainerItems.push(this.configMeta.getLayout());

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
                pimcore.globalmanager.remove('valantic_dataquality_settings');
            });

            pimcore.layout.refresh();
        }

        this.processPayload(payload);

        return this.panel;
    },

    activate: function (payload) {
        const tabPanel = Ext.getCmp('pimcore_panel_tabs');
        tabPanel.setActiveItem('valantic_dataquality_settings');
        this.processPayload(payload);
    },

    processPayload(payload) {
        if (!payload) {
            return;
        }
        if (payload.tab) {
            if (payload.tab === 'constraints') {
                this.settingsContainer.setActiveTab(0);
                if (payload.filter) {
                    console.log(this.configConstraints);
                    this.configConstraints.filterField.setValue(payload.filter);
                    this.configConstraints.doFilter();
                    this.configConstraints.doFilter();
                }
            }
            if (payload.tab === 'meta') {
                this.settingsContainer.setActiveTab(1);
            }
        }
    },

});
