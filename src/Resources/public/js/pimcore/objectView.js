pimcore.registerNS('valantic.dataquality.objectView');
valantic.dataquality.objectView = Class.create({

    initialize: function (object) {
        this.object = object;
    },

    getLayout: function () {
        if (this.layout == null) {
            const modelName = 'valantic.dataquality.report';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    fields: ['attribute', 'label', 'score', 'scores', 'color', 'colors', 'value', 'note', 'type'],
                });
            }

            this.activeGroups = [];
            this.activeIgnoreFallbackLanguage = null;
            const baseStoreProxyConfig = (rootProperty) => ({
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_score_show'),
                noCache: false,
                extraParams: {
                    id: this.object.id,
                    'groups[]': this.activeGroups,
                    ignoreFallbackLanguage: this.activeIgnoreFallbackLanguage,
                },
                reader: {
                    type: 'json',
                    rootProperty,
                },
            });

            this.attributesStoreConfig = () => new Ext.data.Store({
                model: modelName,
                sorters: [
                    {
                        property: 'score',
                        direction: 'ASC',
                    },
                    {
                        property: 'label',
                        direction: 'ASC',
                    },
                ],
                proxy: baseStoreProxyConfig('attributes'),
            });

            this.globalScores = null;
            this.globalColors = null;
            this.objectStoreConfig = () => new Ext.data.Store({
                proxy: baseStoreProxyConfig('object'),
                listeners: {
                    load: function (store) {
                        const data = store.getData()
                            .getAt(0);

                        if (!data.get('color')) {
                            return;
                        }
                        this.layout.setTitle(
                            `${t('valantic_dataquality_pimcore_tab_name')} (<span style="color: ${this.colorMapping(data.get('color'))}">${this.formatAsPercentage(data.get('score'))}</span>)`,
                        );

                        this.globalScores = data.get('scores');
                        this.globalColors = data.get('colors');
                    }.bind(this),
                },
            });

            this.groupsStoreConfig = () => new Ext.data.Store({
                proxy: baseStoreProxyConfig('groups'),
            });

            this.settingsStoreConfig = () => new Ext.data.Store({
                proxy: baseStoreProxyConfig('settings'),
                listeners: {
                    load: function (store) {
                        const data = store.getData()
                            .getAt(0);

                        this.activeIgnoreFallbackLanguage = data.data.ignoreFallbackLanguage;
                    }.bind(this),
                },
            });

            this.attributesStore = this.attributesStoreConfig();
            this.objectStore = this.objectStoreConfig();
            this.groupsStore = this.groupsStoreConfig();
            this.settingsStore = this.settingsStoreConfig();

            const plugins = ['pimcore.gridfilters'];

            // eslint-disable-next-line max-len
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
                {
                    text: t('configure'),
                    iconCls: 'pimcore_icon_properties',
                    handler: function () {
                        const formPanel = new Ext.form.FormPanel({
                            bodyStyle: 'padding:10px;',
                            items: [new Ext.ux.form.MultiSelect({
                                fieldLabel: t('valantic_dataquality_config_constraint_groups'),
                                name: 'groups[]',
                                editable: true,
                                displayField: 'group',
                                valueField: 'group',
                                store: this.groupsStore,
                                mode: 'local',
                                triggerAction: 'all',
                                width: 250,
                                value: this.activeGroups,
                            }),
                            new Ext.form.field.Checkbox({
                                fieldLabel: t('valantic_dataquality_config_constraint_ignore_fallback_languages'),
                                name: 'ignoreFallbackLanguage',
                                editable: true,
                                mode: 'local',
                                displayField: 'ignoreFallbackLanguage',
                                valueField: 'ignoreFallbackLanguage',
                                value: this.activeIgnoreFallbackLanguage,
                            })],
                        });

                        const configWin = new Ext.Window({
                            modal: true,
                            width: 300,
                            height: 400,
                            closable: true,
                            items: [formPanel],
                            buttons: [{
                                text: t('apply'),
                                iconCls: 'pimcore_icon_accept',
                                handler: function () {
                                    const values = formPanel.getForm()
                                        .getFieldValues();

                                    this.activeGroups = values['groups[]'];
                                    // eslint-disable-next-line max-len
                                    this.activeIgnoreFallbackLanguage = values.ignoreFallbackLanguage;

                                    this.attributesStore = this.attributesStoreConfig();
                                    this.objectStore = this.objectStoreConfig();

                                    this.attributesStore.reload();
                                    this.objectStore.reload();

                                    configWin.close();
                                }.bind(this),
                            }],
                        });

                        configWin.show();
                    }.bind(this),
                },
                {
                    text: t('settings'),
                    iconCls: 'pimcore_icon_properties',
                    handler: function () {
                        try {
                            pimcore.globalmanager.get('valantic_dataquality_settings')
                                .activate({
                                    tab: 'constraints',
                                    filter: this.object.data.general.o_className,
                                });
                        } catch (e) {
                            pimcore.globalmanager.add('valantic_dataquality_settings', new valantic.dataquality.settings({
                                tab: 'constraints',
                                filter: this.object.data.general.o_className,
                            }));
                        }
                    }.bind(this),
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
                columns: [
                    {
                        text: t('valantic_dataquality_view_column_attributename'),
                        sortable: true,
                        dataIndex: 'label',
                        editable: false,
                        flex: 1,
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('details'),
                        width: 30,
                        items: [
                            {
                                tooltip: t('details'),
                                icon: '/bundles/pimcoreadmin/img/flat-color-icons/questions.svg',
                                getClass: function (v, metadata, record) {
                                    if (!record.get('note') || record.get('note').length === 0) {
                                        // eslint-disable-next-line no-param-reassign
                                        metadata.style += 'display:none;';
                                    }
                                },
                                handler: function (gridRef, rowIndex, colIndex, item, e, record) {
                                    Ext.Msg.alert(record.get('label'), record.get('note'), Ext.emptyFn);
                                },
                            },
                        ],
                    },
                    {
                        text: t('valantic_dataquality_view_column_value'),
                        sortable: true,
                        dataIndex: 'value_preview',
                        editable: false,
                        flex: 1,
                        renderer: function (value, meta, record) {
                            const preview = record.get('value_preview');
                            let val = record.get('value');
                            if (Array.isArray(val)) {
                                val = `<ul>${val.map((v) => `<li>${v}</li>`)
                                    .join('')}</ul>`;
                            }
                            if ((typeof val === 'object' && val !== null)) {
                                val = `<div>${Object.keys(val)
                                    .map((k) => `<h4>${k}</h4><p>${val[k]}</p>`)
                                    .join('')}</div>`;
                            }
                            return `<div class="show-when-wrapped" style="display:none;">${val}</div><div class="hide-when-wrapped">${preview}</div>`;
                        },
                        cellWrap: false,
                        variableRowHeight: false,
                    },
                    {
                        xtype: 'actioncolumn',
                        menuText: t('details'),
                        width: 30,
                        items: [
                            {
                                tooltip: t('details'),
                                icon: '/bundles/pimcoreadmin/img/flat-color-icons/view_details.svg',
                                handler: function (gridRef, rowIndex, colIndex) {
                                    // eslint-disable-next-line no-param-reassign
                                    const cell = gridRef.getRow(rowIndex)
                                        .querySelector(`${`td:nth-child(${colIndex}`})`);
                                    const wrapClass = 'x-wrap-cell';
                                    if (!cell.classList.contains(wrapClass)) {
                                        cell.classList.add(wrapClass);
                                        cell.querySelector('.hide-when-wrapped').style.display = 'none';
                                        cell.querySelector('.show-when-wrapped').style.display = 'inline-block';
                                    } else {
                                        cell.classList.remove(wrapClass);
                                        cell.querySelector('.show-when-wrapped').style.display = 'none';
                                        cell.querySelector('.hide-when-wrapped').style.display = 'inline-block';
                                    }
                                },
                            },
                        ],
                    },
                    {
                        text: t('valantic_dataquality_view_column_score'),
                        sortable: true,
                        dataIndex: 'score',
                        editable: false,
                        flex: 0,
                        renderer: function (value, meta, record) {
                            // eslint-disable-next-line no-param-reassign
                            meta.style = this.cellStyle(record.get('color'));
                            return this.formatAsPercentage(value);
                        }.bind(this),
                        align: 'right',
                    },
                ],
                region: 'center',
                bbar: this.pagingtoolbar,
                tbar: tbar,
                plugins: plugins,
                viewConfig: {
                    forceFit: true,
                },
                stripeRows: true,
                listeners: {
                    rowclick: function (recordGrid, record) {
                        const label = t('valantic_dataquality_view_locales_for', null, { name: record.get('label') });
                        const note = record.get('note');
                        this.showDetail(record.data.scores, record.data.colors, label, note);
                    }.bind(this),
                },
            });

            grid.on('beforerender', function () {
                this.showDetail(this.globalScores, this.globalColors, t('valantic_dataquality_view_global_locales'));
                this.attributesStore.load();
            }.bind(this));

            grid.reference = this;

            this.detailView = new Ext.Panel({
                region: 'east',
                minWidth: 350,
                width: 350,
                split: true,
            });

            this.layout = new Ext.Panel({
                title: t('valantic_dataquality_pimcore_tab_name'),
                tabConfig: {
                    tooltip: t('valantic_dataquality_pimcore_tab_name'),
                },
                iconCls: 'pimcore_material_icon_info pimcore_material_icon',
                border: false,
                layout: 'border',
                items: [grid, this.detailView],
            });

            this.objectStore.load();
            this.groupsStore.load();
            this.settingsStore.load();
        }

        return this.layout;
    },

    reload: function () {
        this.attributesStore.reload();
        this.objectStore.reload();
        this.groupsStore.reload();
    },

    showDetail: function (scores, colors, label, note = null) {
        const data = [];

        Object.keys(scores)
            .forEach((locale) => data.push({
                locale,
                score: scores[locale],
                color: colors[locale],
            }));

        const store = new Ext.data.Store({
            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                    rootProperty: 'scores',
                },
            },
            autoDestroy: true,
            data: data,
            sorters: [
                {
                    property: 'score',
                    direction: 'ASC',
                },
            ],
        });

        const detailsGrid = new Ext.grid.GridPanel({
            store: store,
            title: label,
            columns: [
                {
                    text: t('valantic_dataquality_view_column_locale'),
                    sortable: true,
                    dataIndex: 'locale',
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
                        meta.style = this.cellStyle(record.get('color'));
                        return this.formatAsPercentage(value);
                    }.bind(this),
                    align: 'right',
                },
            ],
            columnLines: true,
            stripeRows: true,
            autoScroll: true,
            viewConfig: {
                forceFit: true,
            },
        });

        const globalGrid = this.detailView.items.getAt(0);
        this.detailView.removeAll(false);

        if (globalGrid) {
            this.detailView.add(globalGrid);
        }

        if (data.length > 0) {
            this.detailView.add(detailsGrid);
        }
        if (note) {
            this.detailView.add(new Ext.Component({
                xtype: 'component',
                autoEl: {}, // will default to creating a DIV
                html: `<div style="padding: 10px"><div style="position: relative; padding: .75rem 1.25rem; margin-bottom: 1rem; border-radius: .25rem; color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb;">${note}</div></div>`,
            }));
        }

        this.detailView.updateLayout();
    },

    formatAsPercentage: function (v) {
        return (!Number.isNaN(v) ? `${(v * 100).toFixed(0)} %` : '');
    },

    colorMapping: function (color) {
        if (color === 'green') {
            return '#4CAF50;';
        }
        if (color === 'orange') {
            return '#FF9800;';
        }
        if (color === 'red') {
            return '#F44336;';
        }
        return '';
    },

    colorIcon: function (color) {
        if (color === 'green') {
            return '/bundles/pimcoreadmin/img/flat-color-icons/approve.svg';
        }
        if (color === 'orange') {
            return '/bundles/pimcoreadmin/img/flat-color-icons/medium_priority.svg';
        }
        if (color === 'red') {
            return '/bundles/pimcoreadmin/img/flat-color-icons/delete.svg';
        }
        return '';
    },

    cellStyle: function (color) {
        if (color === 'green' || color === 'orange' || color === 'red') {
            return `color: ${this.colorMapping(color)}; background: url('${this.colorIcon(color)}') right center no-repeat; padding-right: 30px;`;
        }
        return '';
    },
});
