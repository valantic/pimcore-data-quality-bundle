pimcore.registerNS("valantic.dataquality.object_view");
valantic.dataquality.object_view = Class.create({

    initialize: function (object) {
        this.object = object;
    },

    getLayout: function () {

        if (this.layout == null) {

            var modelName = 'pvalantic.dataquality.report';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    fields: ['attribute', 'score']
                });
            }

            this.store = new Ext.data.Store({
                model: modelName,
                sorters: [
                    {
                        property: 'attribute',
                        direction: 'ASC'
                    },
                    {
                        property: 'score',
                        direction: 'DESC'
                    }],
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('valantic_dataquality_config_show'),
                    extraParams: {
                        id: this.object.id
                    },
                    // Reader is now on the proxy, as the message was explaining
                    reader: {
                        type: 'json',
                        rootProperty: 'scores'
                    }

                }
            });

            var grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                columns: [
                    {
                        text: t('valantic_dataquality_config_column_attribute'),
                        sortable: true,
                        dataIndex: 'attribute',
                        editable: false,
                        width: 240
                    },
                    {
                        text: t('valantic_dataquality_config_column_score'),
                        sortable: true,
                        dataIndex: 'score',
                        editable: false,
                        width: 200
                    },
                ],
                stripeRows: true,
                width: 440,
            });

            grid.on("beforerender", function () {
                this.store.load();
            }.bind(this));

            grid.reference = this;

            this.iframeId = 'object_version_iframe_' + this.object.id;

            this.layout = new Ext.Panel({
                title: t('valantic_dataquality_pimcore_tab_name'),
                tabConfig: {
                    tooltip: t('valantic_dataquality_pimcore_tab_name')
                },
                iconCls: 'pimcore_material_icon_info pimcore_material_icon',
                bodyStyle: 'padding:20px 5px 20px 5px;',
                border: false,
                layout: "border",
                items: [grid]
            });

        }

        return this.layout;
    },

    reload: function () {
        this.store.reload();
    },

});
