

// TODO : utiliser une classe avec un static ?
const MaxParametersNumber = 6;

export const DataSource = {
    get: (action, parameters) => {
        if(!typeof parameters === 'object' || parameters.length > MaxParametersNumber)
            return new Promise((resolve, reject) => {resolve({error:true, message:'Invalid parameters'})});
        if(typeof action !== 'string')
            return new Promise((resolve, reject) => {resolve({error:true, message:'Invalid action'})});
        const cleanUrl = action.trim().replace(/^\/+|\/+$/, '')
        var fullUrl = Object.entries(parameters).reduce((prev, curr, currIndex) => {
            const res = `${prev}${curr[0]}=${curr[1]}`;
            return currIndex === 0
                ? `${res}`
                : `&${res}`;
        },
        `/${cleanUrl}?`)
        return $.get(fullUrl);
    }
}
