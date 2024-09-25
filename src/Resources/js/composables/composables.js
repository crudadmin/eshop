export const useGetLocaleFieldValue = (value, dslug) => {
    if (_.isNil(dslug)) {
        let languages = useAppStore().languages;

        dslug = languages.length ? languages[0].slug : null;
    }

    if (value && typeof value === 'object') {
        //Get default language value
        if (dslug in value && (value[dslug] || value[dslug] == 0)) {
            value = value[dslug];
        }

        //Get other available language
        else
            for (var key in value) {
                if (value[key] || value[key] === 0) {
                    value = value[key];
                    break;
                }
            }

        if (typeof value == 'object') {
            value = '';
        }
    }

    return value;
};
