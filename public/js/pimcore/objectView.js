pimcore.registerNS('valantic.dataquality.objectView');
valantic.dataquality.objectView = Class.create({

    initialize: function (object) {
        this.object = object;

        document.addEventListener(pimcore.events.postSaveObject, this.reload.bind(this));
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
            this.allowedLocales = [];
            this.activeIgnoreFallbackLanguage = null;

            this.store = Ext.create('Ext.data.Store', {
                autoLoad: true,
                proxy: {
                    type: 'ajax',
                    noCache: false,
                    url: Routing.generate('valantic_dataquality_score_show'),
                    actionMethods: {
                        read: 'GET',
                    },
                    extraParams: {
                        id: this.object.id,
                    },
                    reader: {
                        type: 'json',
                    },
                },
                listeners: {
                    // eslint-disable-next-line no-unused-vars
                    load: (store, records, successful) => {
                        const objectData = records[0].getData().object;
                        const settingsData = records[0].getData().settings;

                        if (objectData.color && objectData.scores) {
                            this.layout.setTitle(
                                `${t('valantic_dataquality_pimcore_tab_name')} (<span style="color: ${objectData.color}">${this.formatAsPercentage(objectData.score)}</span>)`,
                            );

                            this.globalScores = objectData.scores;
                            this.globalColors = objectData.colors;
                        }

                        this.activeGroups = settingsData.groups;
                        this.allowedLocales = settingsData.allowedLocales;
                        this.activeIgnoreFallbackLanguage = settingsData.ignoreFallbackLanguage;

                        this.attributesStore.loadRawData(records[0].getData());
                        this.groupsStore.loadRawData(records[0].getData());

                        if (this.detailView) {
                            this.detailView.removeAll(false);
                        }
                        this.showDetail(this.globalScores, this.globalColors, t('valantic_dataquality_view_global_locales'));
                    },
                },
            });

            this.attributesStore = Ext.create('Ext.data.Store', {
                model: modelName,
                autoAsync: true,
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
                proxy: { type: 'memory', reader: { type: 'json', rootProperty: 'attributes' } },
            });

            this.groupsStore = Ext.create('Ext.data.Store', {
                autoAsync: true,
                proxy: { type: 'memory', reader: { type: 'json', rootProperty: 'groups' } },
            });

            const plugins = ['pimcore.gridfilters'];

            this.filterField = new Ext.form.TextField({
                xtype: 'textfield',
                width: 200,
                style: 'margin: 0 10px 0 0;',
                enableKeyEvents: true,
                listeners: {
                    keyup: {
                        fn: this.filterStore.bind(this),
                        element: 'el',
                    },
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
                    handler: () => {
                        const formPanel = new Ext.form.FormPanel({
                            bodyStyle: 'padding:10px;',
                            items: [
                                new Ext.ux.form.MultiSelect({
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
                                }),
                            ],
                        });

                        const configWin = new Ext.Window({
                            modal: true,
                            width: 300,
                            height: 400,
                            closable: true,
                            items: [formPanel],
                            buttons: [
                                {
                                    text: t('reset'),
                                    tooltip: t('valantic_dataquality_config_settings_user_reset'),
                                    iconCls: 'pimcore_icon_delete',
                                    handler: () => {
                                        Ext.MessageBox.confirm(
                                            t('valantic_dataquality_config_settings_user_reset_confirmation_title'),
                                            t('valantic_dataquality_config_settings_user_reset_confirmation_message'),
                                            (confirmation) => {
                                                if (confirmation === 'yes') {
                                                    const values = {
                                                        // eslint-disable-next-line max-len
                                                        classname: this.object.data.general.className,
                                                    };

                                                    Ext.Ajax.request({
                                                        url: Routing.generate('valantic_dataquality_metaconfig_userreset'),
                                                        method: 'post',
                                                        params: values,
                                                        // eslint-disable-next-line no-unused-vars
                                                        success: (response, opts) => {
                                                            this.store.reload();
                                                        },
                                                    });

                                                    configWin.close();
                                                }
                                            },
                                        );
                                    },
                                },
                                {
                                    text: t('apply'),
                                    iconCls: 'pimcore_icon_accept',
                                    handler: () => {
                                        const values = formPanel.getForm().getFieldValues();

                                        values.classname = this.object.data.general.className;

                                        Ext.Ajax.request({
                                            url: Routing.generate('valantic_dataquality_metaconfig_usermodify'),
                                            method: 'post',
                                            params: values,
                                            // eslint-disable-next-line no-unused-vars
                                            success: (response, opts) => {
                                                this.activeGroups = values['groups[]'];
                                                // eslint-disable-next-line max-len
                                                this.activeIgnoreFallbackLanguage = values.ignoreFallbackLanguage;

                                                this.store.reload();
                                            },
                                        });
                                        configWin.close();
                                    },
                                },
                            ],
                        });

                        configWin.show();
                    },
                },
                {
                    text: t('settings'),
                    iconCls: 'pimcore_icon_properties',
                    handler: () => {
                        try {
                            pimcore.globalmanager.get('valantic_dataquality_settings')
                                .activate({
                                    tab: 'constraints',
                                    filter: this.object.data.general.className,
                                });
                        } catch (e) {
                            pimcore.globalmanager.add('valantic_dataquality_settings', new valantic.dataquality.settings({
                                tab: 'constraints',
                                filter: this.object.data.general.className,
                            }));
                        }
                    },
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
                        renderer: (value, meta, record) => {
                            // eslint-disable-next-line no-param-reassign
                            meta.style = this.cellStyle(record.get('color'));
                            return this.formatAsPercentage(value);
                        },
                        align: 'right',
                    },
                ],
                region: 'center',
                tbar: tbar,
                plugins: plugins,
                viewConfig: {
                    forceFit: true,
                },
                stripeRows: true,
                listeners: {
                    rowclick: (recordGrid, record) => {
                        const label = t('valantic_dataquality_view_locales_for', null, { name: record.get('label') });
                        const note = record.get('note');
                        this.showDetail(record.data.scores, record.data.colors, label, note);
                    },
                },
            });

            grid.reference = this;

            this.detailView = new Ext.Panel({
                region: 'east',
                autoScroll: true,
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

            this.store.load();
        }

        return this.layout;
    },

    reload: function () {
        this.store.reload();
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
                    renderer: function (value) {
                        return `${pimcore.available_languages[value]} (${value})`;
                    },
                },
                {
                    text: t('valantic_dataquality_view_column_score'),
                    sortable: true,
                    dataIndex: 'score',
                    editable: false,
                    width: 100,
                    renderer: (value, meta, record) => {
                        // eslint-disable-next-line no-param-reassign
                        meta.style = this.cellStyle(record.get('color'));
                        return this.formatAsPercentage(value);
                    },
                    align: 'right',
                },
            ],
            style: 'margin-bottom: 20px',
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

    filterStore: function (e) {
        const searchColumns = ['attribute', 'value_preview'];
        const query = Ext.get(e.target).getValue().toLowerCase();
        const searchFilter = new Ext.util.Filter({
            filterFn: function (item) {
                let result = false;
                Object.entries(item.data).forEach(([key, value]) => {
                    /* skip none-search columns and null values */
                    if (value && searchColumns.indexOf(key) >= 0) {
                        const lValue = String(value).toLowerCase();

                        /* numbers, texts */
                        if (lValue.indexOf(query) >= 0) {
                            result = true;
                        }
                    }
                });
                return result;
            },
        });

        this.attributesStore.clearFilter();
        this.attributesStore.filter(searchFilter);
    },
});
