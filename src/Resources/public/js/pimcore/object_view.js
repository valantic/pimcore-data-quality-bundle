pimcore.registerNS('valantic.dataquality.object_view');
valantic.dataquality.object_view = Class.create({

    initialize: function (object) {
        this.object = object;
    },

    getLayout: function () {
        if (this.layout == null) {
            const modelName = 'valantic.dataquality.report';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    fields: ['attribute', 'score', 'scores'],
                });
            }

            const formatAsPercentage = (v) => (!Number.isNaN(v) ? `${(v * 100).toFixed(0)} %` : '');

            this.store = new Ext.data.Store({
                model: modelName,
                sorters: [
                    {
                        property: 'attribute',
                        direction: 'ASC',
                    },
                    {
                        property: 'score',
                        direction: 'DESC',
                    }],
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('valantic_dataquality_score_show'),
                    extraParams: {
                        id: this.object.id,
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'scores',
                    },
                },
                listeners: {
                    load: function (store) {
                        const scoredLocales = store.getData().items
                            .map((item) => item.get('scores'))
                            .filter((item) => !!item)
                            .flatMap((i) => Object.keys(i))
                            .filter((value, index, self) => self.indexOf(value) === index);
                        const columns = [
                            {
                                text: t('valantic_dataquality_view_column_attributename'),
                                sortable: true,
                                dataIndex: 'attribute',
                                editable: false,
                                flex: 1,
                            },
                            {
                                text: t('valantic_dataquality_view_column_score'),
                                sortable: true,
                                dataIndex: 'score',
                                editable: false,
                                flex: 1,
                                renderer: function (value) {
                                    return formatAsPercentage(value);
                                },
                                align: 'right',
                            },
                        ];
                        const localeColumn = (locale) => ({
                            text: `${t('valantic_dataquality_view_column_score')} (${locale})`,
                            sortable: true,
                            dataIndex: 'scores',
                            renderer: function (value) {
                                if(Number.isNaN(value)){
                                    return t('valantic_dataquality_view_not_localized_no_score');
                                }
                                return formatAsPercentage(value[locale]);
                            },
                            editable: false,
                            flex: 1,
                            align: 'right',
                        });
                        scoredLocales.forEach((locale) => columns.push(localeColumn(locale)));
                        // eslint-disable-next-line no-use-before-define
                        grid.setColumns(columns);
                    },
                },
            });

            const grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                columns: [],
                stripeRows: true,
                width: 600, // FIXME: full-width
            });

            grid.on('beforerender', function () {
                this.store.load();
            }.bind(this));

            grid.reference = this;

            this.layout = new Ext.Panel({
                title: t('valantic_dataquality_pimcore_tab_name'),
                tabConfig: {
                    tooltip: t('valantic_dataquality_pimcore_tab_name'),
                },
                iconCls: 'pimcore_material_icon_info pimcore_material_icon',
                bodyStyle: 'padding:20px 5px 20px 5px;',
                border: false,
                layout: 'border',
                items: [grid],
            });
        }

        return this.layout;
    },

    reload: function () {
        // TODO: add button to trigger
        this.store.reload();
    },

});
