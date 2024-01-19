pimcore.registerNS('valantic.dataquality.settings_meta');
valantic.dataquality.settings_meta = Class.create({

    initialize: function (parent) {
        this.parent = parent;

        const data = [];
        pimcore.globalmanager.get('user').contentLanguages.split(',').forEach((key) => {
            data.push({
                locale: key,
                name: pimcore.available_languages[key],
            });
        });

        this.languageStore = Ext.data.Store({
            fields: ['locale', 'name'],
            data: data,
        });
    },

    activate: function () {
        this.getLayout();
    },

    getLayout: function () {
        if (this.layout == null) {
            const itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('valantic_dataquality_metaconfig_list'),
                ['classname', 'locales', 'threshold_green', 'threshold_orange', 'nesting_limit', 'ignore_fallback_language', 'disable_tab_on_object'],
                itemsPerPage,
                {
                    autoLoad: true,
                    remoteFilter: false,
                    sorters: [
                        {
                            property: 'classname',
                            direction: 'ASC',
                        },
                    ],
                },
            );

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

            this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store);

            const tbarItems = [
                {
                    text: t('add'),
                    handler: this.onModify.bind(this),
                    iconCls: 'pimcore_icon_add',
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

            const columns = [
                {
                    text: 'ID',
                    sortable: true,
                    dataIndex: 'id',
                    hidden: true,
                    filter: 'numeric',
                    flex: 60,
                },
                {
                    text: t('valantic_dataquality_config_column_classname'),
                    sortable: true,
                    dataIndex: 'classname',
                    filter: 'string',
                    flex: 200,
                    renderer: Ext.util.Format.htmlEncode,
                },
                {
                    text: t('valantic_dataquality_config_column_locales'),
                    sortable: true,
                    dataIndex: 'locales',
                    filter: 'string',
                    flex: 200,
                    renderer: function (value) {
                        return value.join(', ');
                    },
                },
                {
                    text: t('valantic_dataquality_config_column_threshold_green'),
                    sortable: true,
                    dataIndex: 'threshold_green',
                    filter: 'number',
                    flex: 50,
                },
                {
                    text: t('valantic_dataquality_config_column_threshold_orange'),
                    sortable: true,
                    dataIndex: 'threshold_orange',
                    filter: 'number',
                    flex: 50,
                },
                {
                    text: t('valantic_dataquality_config_column_nesting_limit'),
                    sortable: true,
                    dataIndex: 'nesting_limit',
                    filter: 'number',
                    flex: 50,
                },
                {
                    text: t('valantic_dataquality_config_column_ignore_fallback_language'),
                    sortable: true,
                    dataIndex: 'ignore_fallback_language',
                    filter: 'boolean',
                    flex: 50,
                },
                {
                    text: t('valantic_dataquality_config_column_disable_tab_on_object'),
                    sortable: true,
                    dataIndex: 'disable_tab_on_object',
                    filter: 'boolean',
                    flex: 50,
                },
            ];

            const plugins = ['pimcore.gridfilters'];

            this.grid = new Ext.grid.GridPanel({
                store: this.store,
                region: 'center',
                columns: columns,
                columnLines: true,
                bbar: this.pagingtoolbar,
                tbar: tbar,
                autoExpandColumn: 'locales',
                stripeRows: true,
                autoScroll: true,
                plugins: plugins,
                viewConfig: {
                    forceFit: true,
                },
                listeners: {
                    rowdblclick: this.onModify.bind(this),
                    cellcontextmenu: this.onMainContextmenu.bind(this),
                },
            });

            const layoutConf = {
                items: [this.grid],
                layout: 'border',
            };

            layoutConf.title = t('valantic_dataquality_config_meta_tooltip');

            this.layout = new Ext.Panel(layoutConf);

            this.layout.on('activate', function () {
                this.store.load();
            }.bind(this));
        }

        return this.layout;
    },

    onMainContextmenu: function (tree, td, cellIndex, record, tr, rowIndex, e) {
        const rec = this.store.getAt(rowIndex);

        const menu = new Ext.menu.Menu();
        menu.add([{
            text: t('delete'),
            iconCls: 'pimcore_icon_delete',
            handler: function () {
                Ext.Ajax.request({
                    url: Routing.generate('valantic_dataquality_metaconfig_delete'),
                    method: 'delete',
                    params: {
                        classname: rec.get('classname'),
                        attributename: rec.get('attributename'),
                    },
                    success: function () {
                        this.store.reload();
                    }.bind(this),
                });
            }.bind(this),
        }]);

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    onModify: function (tree, possibleRecord, onlyDefinedIfEdit) {
        const record = onlyDefinedIfEdit ? possibleRecord : null;
        const classesStore = new Ext.data.Store({
            fields: ['name'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_metaconfig_listclasses'),
                reader: {
                    type: 'json',
                    rootProperty: 'classes',
                },
            },
        });

        const localeInfo = Ext.create('Ext.Component', {
            html: t('valantic_dataquality_config_settings_locales'),
            width: 350,
            style: {
                marginTop: '10px',
                marginBottom: '5px',
            },
        });

        const localeCombo = new Ext.ux.form.MultiSelect({
            name: 'locales[]',
            editable: true,
            displayField: 'name',
            valueField: 'locale',
            store: this.languageStore,
            mode: 'local',
            triggerAction: 'all',
            width: 350,
            height: 180,
            value: record ? record.get('locales') : null,
        });

        const classnameCombo = Ext.create('Ext.form.ComboBox', {
            fieldLabel: t('valantic_dataquality_config_column_classname'),
            store: classesStore,
            queryMode: 'local',
            name: 'classname',
            displayField: 'short',
            valueField: 'name',
            width: 350,
            value: record ? record.get('classname') : null,
        });

        const greenRange = {
            xtype: 'numberfield',
            fieldLabel: t('valantic_dataquality_config_column_threshold_green'),
            name: 'threshold_green',
            editable: true,
            width: 250,
            value: record ? record.get('threshold_green') : 90,
            maxValue: 100,
            minValue: 0,
        };

        const orangeRange = {
            xtype: 'numberfield',
            fieldLabel: t('valantic_dataquality_config_column_threshold_orange'),
            name: 'threshold_orange',
            editable: true,
            width: 250,
            value: record ? record.get('threshold_orange') : 60,
            maxValue: 100,
            minValue: 0,
        };

        const nestingLimitRange = {
            xtype: 'numberfield',
            fieldLabel: t('valantic_dataquality_config_column_nesting_limit'),
            name: 'nesting_limit',
            editable: true,
            width: 250,
            value: record ? record.get('nesting_limit') : 1,
            maxValue: 48,
            minValue: 0,
        };

        const ignoreFallbackLanguage = {
            xtype: 'checkboxfield',
            fieldLabel: t('valantic_dataquality_config_column_ignore_fallback_language'),
            name: 'ignore_fallback_language',
            editable: true,
            width: 250,
            value: record ? record.get('ignore_fallback_language') : false,
        };

        const disableTabOnObject = {
            xtype: 'checkboxfield',
            fieldLabel: t('valantic_dataquality_config_column_disable_tab_on_object'),
            name: 'disable_tab_on_object',
            editable: true,
            width: 250,
            value: record ? record.get('disable_tab_on_object') : false,
        };

        const formPanel = new Ext.form.FormPanel({
            bodyStyle: 'padding:10px;',
            // eslint-disable-next-line max-len
            items: [classnameCombo, localeInfo, localeCombo, greenRange, orangeRange, nestingLimitRange, ignoreFallbackLanguage, disableTabOnObject],
        });

        const modifyWin = new Ext.Window({
            modal: true,
            width: 380,
            height: 600,
            closable: true,
            items: [formPanel],
            buttons: [{
                text: t('reset'),
                tooltip: t('valantic_dataquality_config_settings_reset'),
                iconCls: 'pimcore_icon_delete',
                handler: function () {
                    Ext.MessageBox.confirm(t('valantic_dataquality_config_settings_reset_confirmation_title'), t('valantic_dataquality_config_settings_reset_confirmation_message'), function (confirmation) {
                        if (confirmation === 'yes') {
                            const values = formPanel.getForm()
                                .getFieldValues();

                            Ext.Ajax.request({
                                url: Routing.generate('valantic_dataquality_metaconfig_reset'),
                                method: 'post',
                                params: {
                                    classname: values.classname.split('\\').pop(),
                                },
                                success: function () {
                                    this.store.reload();
                                }.bind(this),
                            });

                            modifyWin.close();
                        }
                    }.bind(this));
                }.bind(this),

            }, {
                text: t('save'),
                iconCls: 'pimcore_icon_accept',
                handler: function () {
                    const values = formPanel.getForm()
                        .getFieldValues();

                    Ext.Ajax.request({
                        url: Routing.generate('valantic_dataquality_metaconfig_modify'),
                        method: 'post',
                        params: values,
                        success: function () {
                            this.store.reload();
                        }.bind(this),
                    });

                    modifyWin.close();
                }.bind(this),
            }],
        });

        modifyWin.on('beforerender', function () {
            classesStore.load();
        });

        modifyWin.show();
    },
});
