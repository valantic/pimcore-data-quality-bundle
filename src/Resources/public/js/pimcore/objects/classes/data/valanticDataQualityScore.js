pimcore.registerNS('pimcore.object.classes.data.valanticDataQualityScore');
// eslint-disable-next-line max-len
pimcore.object.classes.data.valanticDataQualityScore = Class.create(pimcore.object.classes.data.data, {

    type: 'valanticDataQualityScore',
    /**
     * define where this datatype is allowed
     */
    allowIn: {
        object: true,
    },

    initialize: function (treeNode, initData) {
        this.type = 'valanticDataQualityScore';

        this.initData(initData);

        this.treeNode = treeNode;
    },

    getTypeName: function () {
        return t('valantic_dataquality_data_type_score');
    },

    getIconClass: function () {
        return 'pimcore_icon_numeric';
    },
});
