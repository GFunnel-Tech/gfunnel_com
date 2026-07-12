/**
 * Copyright (c) AQB Soft - http://www.aqbsoft.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Affiliate System Affiliate System
 * @ingroup     UnaModules
 *
 * @{
 */

!(function(){
    class AqbAnalytics {
        constructor() {
            const { src } = document.currentScript,
                  oUrl = (new URL(src)),
                  aMatches = oUrl.pathname.match(/^(.*)\/modules\//);

            this.path = oUrl.origin;
            if (aMatches && aMatches.length && typeof aMatches[1] !== 'undefined')
                this.path = this.path + aMatches[1];

            console.log(this.path);
        }
        render(oParams) {
            const { ads } = oParams;
            this.get(`${this.path}/modules/index.php?r=affiliate/generate_ads/`, oParams, (oData) => {
                    const oObject = document.querySelector(`div[id*=${ads}]`);
                    if (+!oData.code && oObject) {
                        oObject.innerHTML = oData.html;
                        const oBanner = oObject.getElementsByTagName('a')[0];
                        if (oBanner)
                            oBanner.addEventListener('click', (oEvent) => {
                                oEvent.preventDefault();
                                this.get(`${this.path}/modules/index.php?r=affiliate/perform_action/`, oParams, () => window.location.href = oBanner.href );
                            });
                    }
                    else if (oData.message)
                        console.log(oData.message);
                }
                ,'json');
        }
        get(sUrl, oParams, fCallback){
            fetch(sUrl + '&' + Object.entries(oParams).map(([key, val]) => `${key}=${val}`).join('&'), {
                    method: 'GET',
                    cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
                    headers: {
                        'Content-Type': 'application/json'
                    },
                })
                .then(response  => {
                    if (response.ok) {
                        response
                            .json()
                            .then((json) => typeof fCallback === 'function' && fCallback(json));
                    }
                })
               .catch( error => console.error('Error:', error) );
        }
    }

    const { AqbAffAnalytics } = window;
    const oAds = new AqbAnalytics();
    const execute = function(oParams){
        const { action, params } = oParams;
        if (!action)
            return ;

        return oAds[action].apply(oAds, [params]);
    };

    if (Array.isArray(AqbAffAnalytics) && AqbAffAnalytics.length)
        AqbAffAnalytics.map(oParams => typeof oParams === 'object' && execute(oParams));

}());

/** @} */
