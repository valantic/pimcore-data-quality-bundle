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
                    fields: ['attribute', 'score', 'scores', 'color', 'colors'],
                });
            }

            const formatAsPercentage = (v) => (!Number.isNaN(v) ? `${(v * 100).toFixed(0)} %` : '');

            const colorStyle = (color) => {
                if (color === 'green') {
                    return 'color: #4CAF50; background: url(\'/bundles/pimcoreadmin/img/flat-color-icons/approve.svg\') left center no-repeat;';
                }
                if (color === 'orange') {
                    return 'color: #FF9800; background: url(\'/bundles/pimcoreadmin/img/flat-color-icons/medium_priority.svg\') left center no-repeat;';
                }
                if (color === 'red') {
                    return 'color: #F44336; background: url(\'/bundles/pimcoreadmin/img/flat-color-icons/delete.svg\') left center no-repeat;';
                }
                return '';
            };

            this.store = new Ext.data.Store({
                model: modelName,
                sorters: [
                    {
                        property: 'score',
                        direction: 'ASC',
                    },
                ],
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('valantic_dataquality_score_show'),
                    extraParams: {
                        id: this.object.id,
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'attributes',
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
                                renderer: function (value, meta, record) {
                                    // eslint-disable-next-line no-param-reassign
                                    meta.style = colorStyle(record.get('color'));
                                    return formatAsPercentage(value);
                                },
                                align: 'right',
                            },
                        ];
                        const localeColumn = (locale) => ({
                            text: `${t('valantic_dataquality_view_column_score')} (${locale})`,
                            sortable: true,
                            dataIndex: 'scores',
                            renderer: function (value, meta, record) {
                                // eslint-disable-next-line no-param-reassign
                                meta.style = colorStyle(record.get('colors')[locale]);
                                if (Number.isNaN(value)) {
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

            const plugins = ['pimcore.gridfilters'];

            this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

            this.filterField = new Ext.form.TextField({
                xtype: 'textfield',
                width: 200,
                style: 'margin: 0 10px 0 0;',
                enableKeyEvents: true,
                listeners: {
                    keyup: function (field, key) {
                        if (key.getKey() === key.ENTER || field.getValue().length === 0) {
                            const input = field;
                            const proxy = this.store.getProxy();
                            proxy.extraParams.filterText = input.getValue();

                            this.store.load();
                        }
                    }.bind(this),
                },
            });

            const tbarItems = [
                {
                    text: t('refresh'),
                    iconCls: 'pimcore_icon_reload',
                    handler: this.reload.bind(this),
                },
                '->',
                {
                    text: `${t('filter')}/${t('search')}`,
                    xtype: 'tbtext',
                    style: 'margin: 0 10px 0 0;',
                },
                this.filterField,
            ];

            const tbar = Ext.create('Ext.Toolbar', {
                cls: 'pimcore_main_toolbar',
                items: tbarItems,
            });

            const grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                columns: [],
                region: 'center',
                bbar: this.pagingtoolbar,
                tbar: tbar,
                plugins: plugins,
                viewConfig: {
                    forceFit: true,
                },
                stripeRows: true,
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
                border: false,
                layout: 'border',
                items: [grid],
            });
        }

        return this.layout;
    },

    reload: function () {
        this.store.reload();
    },

});
