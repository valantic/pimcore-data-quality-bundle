pimcore.registerNS('valantic.dataquality.editor');
valantic.dataquality.editor = Class.create({

    // eslint-disable-next-line no-unused-vars
    initialize: function (element, type) {
        const tabPanel = Ext.getCmp('pimcore_panel_tabs');
        tabPanel.add(this.getLayout());
        tabPanel.setActiveTab(this.getLayout());

        this.getLayout().on('destroy', function () {
            pimcore.globalmanager.remove('valantic_dataquality_editor');
        });

        pimcore.layout.refresh();
    },

    activate: function () {
        const tabPanel = Ext.getCmp('pimcore_panel_tabs');
        tabPanel.activate(this.getLayout());
    },

    getLayout: function () {
        if (this.layout == null) {
            const itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('valantic_dataquality_config_list'),
                ['classname', 'attribute', 'rules'],
                itemsPerPage,
                {
                    autoLoad: true,
                    remoteFilter: false,
                    sorters: [
                        {
                            property: 'classname',
                            direction: 'ASC',
                        },
                        {
                            property: 'attributename',
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
                    handler: this.onAddMain.bind(this),
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
                    text: 'ID', sortable: true, dataIndex: 'id', hidden: true, filter: 'numeric', flex: 60,
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
                    text: t('valantic_dataquality_config_column_attributename'),
                    sortable: true,
                    dataIndex: 'attributename',
                    filter: 'string',
                    flex: 200,
                    renderer: Ext.util.Format.htmlEncode,
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
                autoExpandColumn: 'description',
                stripeRows: true,
                autoScroll: true,
                plugins: plugins,
                viewConfig: {
                    forceFit: true,
                },
                listeners: {
                    // eslint-disable-next-line no-unused-vars
                    rowclick: function (grid, record, tr, rowIndex, e, eOpts) {
                        this.showDetail(record);
                    }.bind(this),
                    cellcontextmenu: this.onMainContextmenu.bind(this),
                },
            });

            this.detailView = new Ext.Panel({
                region: 'east',
                minWidth: 350,
                width: 350,
                split: true,
                layout: 'fit',
            });

            const layoutConf = {
                tabConfig: {
                    tooltip: t('valantic_dataquality_config_tooltip'),
                },
                iconCls: 'pimcore_nav_icon_object',
                items: [this.grid, this.detailView],
                layout: 'border',
            };

            layoutConf.title = t('valantic_dataquality_config_tooltip');

            this.layout = new Ext.Panel(layoutConf);

            this.layout.on('activate', function () {
                this.store.load();
            }.bind(this));
        }

        return this.layout;
    },

    // eslint-disable-next-line no-unused-vars
    onMainContextmenu: function (tree, td, cellIndex, record, tr, rowIndex, e, eOpts) {
        const rec = this.store.getAt(rowIndex);

        const menu = new Ext.menu.Menu();
        menu.add([{
            text: t('delete'),
            iconCls: 'pimcore_icon_delete',
            handler: function () {
                Ext.Ajax.request({
                    url: Routing.generate('valantic_dataquality_config_deleteattribute'),
                    method: 'delete',
                    params: {
                        classname: rec.get('classname'),
                        attributename: rec.get('attributename'),
                    },
                    // eslint-disable-next-line no-unused-vars
                    success: function (response, opts) {
                        this.store.reload();
                    }.bind(this),
                });
            }.bind(this),
        }]);

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    // eslint-disable-next-line no-unused-vars
    onDetailContextmenu: function (tree, td, cellIndex, record, tr, rowIndex, e, eOpts) {
        const menu = new Ext.menu.Menu();
        menu.add([{
            text: t('delete'),
            iconCls: 'pimcore_icon_delete',
            handler: function () {
                Ext.Ajax.request({
                    url: Routing.generate('valantic_dataquality_config_deleteconstraint'),
                    method: 'delete',
                    params: {
                        classname: this.record.get('classname'),
                        attributename: this.record.get('attributename'),
                        constraint: record.get('constraint'),
                    },
                    // eslint-disable-next-line no-unused-vars
                    success: function (response, opts) {
                        this.store.reload({
                            // eslint-disable-next-line no-unused-vars
                            callback: function (records, operation, success) {
                                const updatedRecord = this.store.data.items
                                    .filter((r) => r.get('classname') === this.record.get('classname'))
                                    .filter((r) => r.get('attributename') === this.record.get('attributename'))[0];

                                this.showDetail(this.store.getById(updatedRecord.getId()));
                            }.bind(this),
                        });
                    }.bind(this),
                });
            }.bind(this),
        }]);

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    showDetail: function (rec) {
        this.record = rec;

        const detailsStore = new Ext.data.Store({
            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                    rootProperty: 'rules',
                },
            },
            autoDestroy: true,
            data: rec.data,
        });

        const detailsGrid = new Ext.grid.GridPanel({
            store: detailsStore,
            title: `${t('valantic_dataquality_config_details_for')} ${rec.get('classname')}.${rec.get('attributename')}`,
            columns: [
                {
                    text: t('valantic_dataquality_config_column_constraint'),
                    sortable: true,
                    dataIndex: 'constraint',
                    flex: 60,
                },
                {
                    text: t('valantic_dataquality_config_column_parameters'),
                    sortable: true,
                    dataIndex: 'args',
                    flex: 30,
                    // eslint-disable-next-line no-unused-vars
                    renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                        return value ? JSON.stringify(value) : '';
                    },
                },
            ],
            columnLines: true,
            stripeRows: true,
            autoScroll: true,
            viewConfig: {
                forceFit: true,
            },
            listeners: {
                cellcontextmenu: this.onDetailContextmenu.bind(this),
            },
        });

        const detailTbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t('add'),
                    handler: this.onAddDetail.bind(this),
                    iconCls: 'pimcore_icon_add',
                },
            ],
        });

        this.detailView.removeAll();
        if (this.detailView.getDockedItems().length === 0) {
            this.detailView.addDocked(detailTbar);
        }
        this.detailView.add(detailsGrid);
        this.detailView.updateLayout();
    },

    onAddMain: function () {
        const classesStore = new Ext.data.Store({
            fields: ['name'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_config_listclasses'),
                reader: {
                    type: 'json',
                    rootProperty: 'classes',
                },
            },
        });

        const attributesStore = new Ext.data.Store({
            fields: ['name'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_config_listattributes'),
                extraParams: {
                    classname: '',
                },
                reader: {
                    type: 'json',
                    rootProperty: 'attributes',
                },
            },
        });

        const attributenameCombo = new Ext.form.field.ComboBox({
            xtype: 'combo',
            fieldLabel: t('valantic_dataquality_config_column_attributename'),
            name: 'attributename',
            editable: true,
            displayField: 'name',
            valueField: 'name',
            store: attributesStore,
            mode: 'local',
            triggerAction: 'all',
            width: 250,
        });

        const classnameCombo = {
            xtype: 'combo',
            fieldLabel: t('valantic_dataquality_config_column_classname'),
            name: 'classname',
            editable: true,
            displayField: 'name',
            valueField: 'name',
            store: classesStore,
            mode: 'local',
            triggerAction: 'all',
            width: 250,
            listeners: {
                // eslint-disable-next-line no-unused-vars
                select: function (combo, value, index) {
                    const classname = combo.getValue();
                    attributesStore.getProxy().setExtraParams({
                        classname: classname,
                    });
                    attributesStore.load();
                    attributenameCombo.clearValue();
                },
            },
        };

        const formPanel = new Ext.form.FormPanel({
            bodyStyle: 'padding:10px;',
            items: [classnameCombo, attributenameCombo],
        });

        const addMainWin = new Ext.Window({
            modal: true,
            width: 300,
            height: 200,
            closable: true,
            items: [formPanel],
            buttons: [{
                text: t('save'),
                iconCls: 'pimcore_icon_accept',
                handler: function () {
                    const values = formPanel.getForm().getFieldValues();

                    Ext.Ajax.request({
                        url: Routing.generate('valantic_dataquality_config_addattribute'),
                        method: 'post',
                        params: values,
                        // eslint-disable-next-line no-unused-vars
                        success: function (response, opts) {
                            this.store.reload();
                        }.bind(this),
                    });

                    addMainWin.close();
                }.bind(this),
            }],
        });

        addMainWin.show();
    },
    onAddDetail: function () {
        const constraintsStore = new Ext.data.Store({
            fields: ['name'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_config_listconstraints'),
                reader: {
                    type: 'json',
                    rootProperty: 'constraints',
                },
            },
        });

        const formPanel = new Ext.form.FormPanel({
            bodyStyle: 'padding:10px;',
            items: [
                {
                    xtype: 'combo',
                    fieldLabel: t('valantic_dataquality_config_column_constraint'),
                    name: 'constraint',
                    editable: true,
                    displayField: 'name',
                    valueField: 'name',
                    store: constraintsStore,
                    mode: 'local',
                    triggerAction: 'all',
                    width: 400,
                },
                {
                    xtype: 'textareafield',
                    fieldLabel: t('valantic_dataquality_config_column_parameters'),
                    name: 'params',
                    editable: true,
                    width: 400,
                    height: 200,
                },
            ],
        });

        const addDetailWin = new Ext.Window({
            modal: true,
            width: 450,
            height: 350,
            closable: true,
            items: [formPanel],
            buttons: [{
                text: t('save'),
                iconCls: 'pimcore_icon_accept',
                handler: function () {
                    const values = formPanel.getForm().getFieldValues();
                    Ext.Ajax.request({
                        url: Routing.generate('valantic_dataquality_config_addconstraint'),
                        method: 'post',
                        params: {
                            ...values,
                            classname: this.record.get('classname'),
                            attributename: this.record.get('attributename'),
                        },
                        // eslint-disable-next-line no-unused-vars
                        success: function (response, opts) {
                            this.store.reload({
                                // eslint-disable-next-line no-unused-vars
                                callback: function (records, operation, success) {
                                    const updatedRecord = this.store.data.items
                                        .filter((record) => record.get('classname') === this.record.get('classname'))
                                        .filter((record) => record.get('attributename') === this.record.get('attributename'))[0];

                                    this.showDetail(this.store.getById(updatedRecord.getId()));
                                }.bind(this),
                            });
                        }.bind(this),
                    });

                    addDetailWin.close();
                }.bind(this),
            }],
        });

        addDetailWin.show();
    },
});
