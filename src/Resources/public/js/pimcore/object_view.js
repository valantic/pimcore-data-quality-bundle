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
                    return 'color: #4CAF50; background: url(\'/bundles/pimcoreadmin/img/flat-color-icons/approve.svg\') right center no-repeat; padding-right: 30px;';
                }
                if (color === 'orange') {
                    return 'color: #FF9800; background: url(\'/bundles/pimcoreadmin/img/flat-color-icons/medium_priority.svg\') right center no-repeat; padding-right: 30px;';
                }
                if (color === 'red') {
                    return 'color: #F44336; background: url(\'/bundles/pimcoreadmin/img/flat-color-icons/delete.svg\') right center no-repeat; padding-right: 30px;';
                }
                return '';
            };

            this.attributesStore = new Ext.data.Store({
                model: modelName,
                sorters: [
                    {
                        property: 'score',
                        direction: 'ASC',
                    },
                    {
                        property: 'attribute',
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
                                renderer: function (value, meta, record) {
                                    // eslint-disable-next-line no-param-reassign
                                    meta.style = colorStyle(record.get('color'));
                                    return value;
                                },
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

            this.objectStore = new Ext.data.Store({
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('valantic_dataquality_score_show'),
                    extraParams: {
                        id: this.object.id,
                    },
                    reader: {
                        type: 'json',
                        rootProperty: 'object',
                    },
                },
                listeners: {
                    load: function (store) {
                        const data = store.getData().getAt(0);
                        this.layout.setTitle(`${t('valantic_dataquality_pimcore_tab_name')}: ${formatAsPercentage(data.get('score'))}`);
                    }.bind(this),
                },
            });

            const plugins = ['pimcore.gridfilters'];

            this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.attributesStore);

            this.filterField = new Ext.form.TextField({
                xtype: 'textfield',
                width: 200,
                style: 'margin: 0 10px 0 0;',
                enableKeyEvents: true,
                listeners: {
                    keyup: function (field, key) {
                        if (key.getKey() === key.ENTER || field.getValue().length === 0) {
                            const input = field;
                            const proxy = this.attributesStore.getProxy();
                            proxy.extraParams.filterText = input.getValue();

                            this.attributesStore.load();
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
                store: this.attributesStore,
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
                this.attributesStore.load();
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

            this.objectStore.load();
        }

        return this.layout;
    },

    reload: function () {
        this.attributesStore.reload();
        this.objectStore.reload();
    },

});
