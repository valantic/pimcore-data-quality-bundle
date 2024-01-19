pimcore.registerNS('pimcore.object.gridcolumn.operator.dataquality_score');

// eslint-disable-next-line max-len
pimcore.object.gridcolumn.operator.dataquality_score = Class.create(pimcore.object.gridcolumn.Abstract, {
    type: 'operator',
    class: 'dataquality_score',
    iconCls: 'pimcore_icon_numeric',
    defaultText: 'valantic_dataquality_view_column_score',
    group: 'valantic_dataquality_pimcore_tab_name',

    getConfigTreeNode: function (configAttributes) {
        const node = {
            draggable: true,
            iconCls: this.iconCls,
            isOperator: true,
            isTarget: true,
            isChildAllowed: this.allowChild,
        };

        if (configAttributes) {
            node.text = this.getNodeLabel(configAttributes);
            node.configAttributes = configAttributes;
            node.expanded = true;
            node.leaf = false;
            node.expandable = false;
        } else {
            node.text = t(this.defaultText);
            node.configAttributes = {
                type: this.type,
                class: this.class,
            };
            node.leaf = true;
        }

        return node;
    },

    getCopyNode: function (source) {
        const copy = source.createNode({
            iconCls: this.iconCls,
            text: source.data.text,
            isTarget: true,
            leaf: false,
            expandable: false,
            isOperator: true,
            isChildAllowed: this.allowChild,
            configAttributes: {
                label: source.data.text,
                type: this.type,
                class: this.class,
            },
        });

        return copy;
    },

    getConfigDialog: function (node) {
        this.node = node;

        this.textField = new Ext.form.TextField({
            fieldLabel: t('label'),
            length: 255,
            width: 200,
            value: this.node.data.configAttributes.label,
        });

        this.configPanel = new Ext.Panel({
            layout: 'form',
            bodyStyle: 'padding: 10px;',
            items: [this.textField],
            buttons: [{
                text: t('apply'),
                iconCls: 'pimcore_icon_apply',
                handler: function () {
                    this.commitData();
                }.bind(this),
            }],
        });

        this.window = new Ext.Window({
            width: 400,
            height: 200,
            modal: true,
            title: t('localeswitcher_operator_settings'),
            layout: 'fit',
            items: [this.configPanel],
        });

        this.window.show();
        return this.window;
    },

    commitData: function () {
        this.node.data.configAttributes.label = this.textField.getValue();

        const nodeLabel = this.getNodeLabel(this.node.data.configAttributes);
        this.node.set('text', nodeLabel);
        this.node.set('isOperator', true);

        this.window.close();
    },

    allowChild: function (targetNode) {
        if (targetNode.childNodes.length > 0) {
            return false;
        }
        return true;
    },

    getNodeLabel: function (configAttributes) {
        return configAttributes.label;
    },
});
