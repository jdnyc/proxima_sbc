var metadataComponents = [
    'Custom.PosterPanel',
    'Custom.ItemMatchListGrid',
    'Custom.TagLabel',
    'Custom.TagField',
    'Custom.TagPanel',
    'Custom.RadioGroup',
    'Custom.CheckboxGroup',
];
Ext.Loader.load(getComponentUrls(metadataComponents), function () {
    proximaWebUploader(option, UPLOAD_URL);
});
return;