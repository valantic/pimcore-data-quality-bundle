pimcore.registerNS('valantic.dataquality.settings_constraints');
valantic.dataquality.settings_constraints = Class.create({

    initialize: function (parent) {
        this.parent = parent;
    },

    activate: function () {
        const tabPanel = Ext.getCmp('pimcore_panel_tabs');
        tabPanel.activate(this.getLayout());
    },

    getLayout: function () {
        if (this.layout == null) {
            const itemsPerPage = pimcore.helpers.grid.getDefaultPageSize();
            this.store = pimcore.helpers.grid.buildDefaultStore(
                Routing.generate('valantic_dataquality_constraintconfig_list'),
                ['classname', 'attributename', 'rules_count', 'rules', 'note'],
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
                    handler: this.onModifyMain.bind(this),
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
                {
                    text: t('valantic_dataquality_config_column_note'),
                    sortable: true,
                    dataIndex: 'note',
                    filter: 'string',
                    flex: 200,
                    renderer: Ext.util.Format.htmlEncode,
                },
                {
                    text: t('valantic_dataquality_config_column_rules_count'),
                    sortable: true,
                    dataIndex: 'rules_count',
                    filter: 'number',
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
                autoExpandColumn: 'attributename',
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
                    rowdblclick: this.onModifyMain.bind(this),
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
                items: [this.grid, this.detailView],
                layout: 'border',
            };

            layoutConf.title = t('valantic_dataquality_config_constraints_tooltip');

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
                    url: Routing.generate('valantic_dataquality_constraintconfig_deleteattribute'),
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
                    url: Routing.generate('valantic_dataquality_constraintconfig_deleteconstraint'),
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

                                if (updatedRecord) {
                                    this.showDetail(this.store.getById(updatedRecord.getId()));
                                }
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
                rowdblclick: this.onModifyDetail.bind(this),
            },
        });

        const detailTbar = Ext.create('Ext.Toolbar', {
            cls: 'pimcore_main_toolbar',
            items: [
                {
                    text: t('add'),
                    handler: this.onModifyDetail.bind(this),
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

    onModifyMain: function (tree, possibleRecord, onlyDefinedIfEdit) {
        const record = onlyDefinedIfEdit ? possibleRecord : null;
        const classesStore = new Ext.data.Store({
            fields: ['name'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_constraintconfig_listclasses'),
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
                url: Routing.generate('valantic_dataquality_constraintconfig_listattributes'),
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
            value: record ? record.get('attributename') : null,
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
            value: record ? record.get('classname') : null,
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
        const noteTextare = {
            xtype: 'textareafield',
            fieldLabel: t('valantic_dataquality_config_column_note'),
            name: 'note',
            editable: true,
            width: 250,
            height: 100,
            value: record ? record.get('note') : null,
        };
        const formPanel = new Ext.form.FormPanel({
            bodyStyle: 'padding:10px;',
            items: [classnameCombo, attributenameCombo, noteTextare],
        });

        const addMainWin = new Ext.Window({
            modal: true,
            width: 300,
            height: 300,
            closable: true,
            items: [formPanel],
            buttons: [{
                text: t('save'),
                iconCls: 'pimcore_icon_accept',
                handler: function () {
                    const values = formPanel.getForm().getFieldValues();

                    Ext.Ajax.request({
                        url: Routing.generate('valantic_dataquality_constraintconfig_addattribute'),
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
    onModifyDetail: function (tree, possibleRecord, onlyDefinedIfEdit) {
        const record = onlyDefinedIfEdit ? possibleRecord : null;
        const constraintsStore = new Ext.data.Store({
            fields: ['name', 'label'],
            proxy: {
                type: 'ajax',
                url: Routing.generate('valantic_dataquality_constraintconfig_listconstraints'),
                reader: {
                    type: 'json',
                    rootProperty: 'constraints',
                },
            },
            listeners: {
                load: function () {
                    if (record) {
                        // eslint-disable-next-line no-use-before-define
                        refreshConstraintParametersHelper(record.get('constraint'));
                    }
                },
            },
        });

        constraintsStore.load();

        const constraintParametersHelper = new Ext.Component({
            xtype: 'component',
            autoEl: {}, // will default to creating a DIV
            html: '',
        });

        const refreshConstraintParametersHelper = (constraint) => {
            const requiredParameters = constraintsStore.byValue.get(constraint).get('required_parameters');
            const optionalParameters = constraintsStore.byValue.get(constraint).get('optional_parameters');
            const defaultParameter = constraintsStore.byValue.get(constraint).get('default_parameter');
            constraintParametersHelper.setHtml(`<p style="word-break: break-all;">${t('valantic_dataquality_config_constraint_parameters_text', null, {
                constraint,
                defaultParameter: defaultParameter || ' - ',
                optionalParameters: JSON.stringify(optionalParameters),
                requiredParameters: JSON.stringify(requiredParameters),
            })}</p>`);
        };

        const formPanel = new Ext.form.FormPanel({
            bodyStyle: 'padding:10px;',
            items: [
                {
                    xtype: 'combo',
                    fieldLabel: t('valantic_dataquality_config_column_constraint'),
                    name: 'constraint',
                    editable: true,
                    displayField: 'label',
                    valueField: 'name',
                    store: constraintsStore,
                    mode: 'local',
                    triggerAction: 'all',
                    width: 400,
                    value: record ? record.get('constraint') : null,
                    listeners: {
                        select: function (combo, value) {
                            refreshConstraintParametersHelper(combo.getValue(), value);
                        },
                    },
                },
                constraintParametersHelper,
                {
                    xtype: 'textareafield',
                    fieldLabel: t('valantic_dataquality_config_column_parameters'),
                    name: 'params',
                    editable: true,
                    width: 400,
                    height: 200,
                    value: record && record.get('args') ? JSON.stringify(record.get('args')) : null,
                    validator: function (value) {
                        if (!formPanel.getValues().constraint) {
                            return true;
                        }
                        const selectedConstraint = formPanel.items.getAt(0).getSelection();

                        if (!selectedConstraint) {
                            return true;
                        }

                        const defaultParameter = selectedConstraint.get('default_parameter');
                        const requiredParameters = Object.keys(selectedConstraint.get('required_parameters'));

                        const hasDefaultParameter = !!defaultParameter;
                        const hasRequiredParameters = !!requiredParameters.length;

                        const valueIsEmpty = (!value || !value.trim().length);

                        // if there is/are neither a default parameter nor any required parameters,
                        // empty strings are fine
                        if ((!hasDefaultParameter && !hasRequiredParameters) && valueIsEmpty) {
                            return true;
                        }

                        let valueIsJson = false;
                        let parsedValue = null;
                        try {
                            parsedValue = JSON.parse(value);
                            valueIsJson = true;
                        } catch (e) {
                            //
                        }

                        if (hasDefaultParameter && valueIsEmpty) {
                            return t('valantic_dataquality_config_constraint_parameters_invalid_default_missing');
                        }

                        // if the constraint supports a default parameter,
                        // the value doesn't have to be JSON
                        if (hasDefaultParameter && !valueIsEmpty) {
                            return true;
                        }

                        // otherwise the value needs to be JSON
                        if (!valueIsJson) {
                            return t('valantic_dataquality_config_constraint_parameters_invalid_not_json');
                        }

                        const configuredParameters = Object.keys(parsedValue);

                        // if the constraint has required parameters, ensure all are configured
                        if (hasRequiredParameters) {
                            const configuredRequiredParameters = requiredParameters
                                .filter((param) => configuredParameters.includes(param));
                            if (requiredParameters.length !== configuredRequiredParameters.length) {
                                return t('valantic_dataquality_config_constraint_parameters_invalid_required_missing');
                            }
                        }

                        return true;
                    },
                },
            ],
        });

        const addDetailWin = new Ext.Window({
            modal: true,
            width: 450,
            height: 500,
            closable: true,
            items: [formPanel],
            buttons: [{
                text: t('save'),
                iconCls: 'pimcore_icon_accept',
                handler: function () {
                    if (!formPanel.getForm().isValid()) {
                        Ext.Msg.alert('', t('valantic_dataquality_config_constraint_form_invalid'), Ext.emptyFn);
                        return;
                    }
                    const values = formPanel.getForm().getFieldValues();
                    Ext.Ajax.request({
                        url: Routing.generate('valantic_dataquality_constraintconfig_addconstraint'),
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
                                        .filter((rec) => rec.get('classname') === this.record.get('classname'))
                                        .filter((rec) => rec.get('attributename') === this.record.get('attributename'))[0];

                                    if (updatedRecord) {
                                        this.showDetail(this.store.getById(updatedRecord.getId()));
                                    }
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
