<?php defined('BX_DOL') or die('hack attempt');
/**
 *     copyright            : (C) 2018 AQB Soft
 *     website              : http://www.aqbsoft.com
 *
 * IMPORTANT: This is a commercial product made by AQB Soft. It cannot be modified for other than personal usage.
 * The "personal usage" means the product can be installed and set up for ONE domain name ONLY.
 * To be able to use this product for another domain names you have to order another copy of this product (license).
 *
 * This product cannot be redistributed for free or a fee without written permission from AQB Soft.
 *
 * This notice may not be removed from the source code.
 *
 * @defgroup    Autoonline Autoonline
 * @ingroup     UnaModules
 *
 * @{
 */

bx_import('BxTemplFormView');

class AqbAutoonlineRulesForm extends BxTemplFormView
{
    public function __construct($aInfo, $oTemplate) {
        parent::__construct($aInfo, $oTemplate);
    }

    protected function genCustomInputTime(&$aInput) {
        ob_start();
?>
        <script type="text/javascript">
            function aqb_autoonline_init_custom_range() {
                var onCreateRange = function (event, ui) {
                    var eInput = $(this).parent().find('input');
                    //var iSliderWidth = eInput.innerWidth() - 160;
                    var iSliderWidth = 300;
                    $(this).css('width',  iSliderWidth + 'px').css('top', (eInput.innerHeight() - $(this).outerHeight())/2 + 1 + 'px');
                    eInput.css('padding-right', (iSliderWidth + parseFloat($(this).css('margin-right')) + 10) + 'px');
                };

                var cur = $('input[type=doublerange_time]');
                if(cur.hasClass('bx-form-doublerange-processed'))
                    return;

                cur.addClass('bx-form-doublerange-processed');
                cur.val('00:00 - 23:59');

                var $slider = $('<div class="bx-def-margin-right"></div>').insertAfter(cur);

                var iMin = cur.attr("min") ? parseFloat(cur.attr("min"), 10) : 0;
                var iMax = cur.attr("max") ? parseFloat(cur.attr("max"), 10) : 100;

                var funcGetValues = function (e) {
                    var values = e.val().match(/(\d+):(\d+) - (\d+):(\d+)/);
                    if (!values) {
                        alert('<?= _t('_aqb_autoonline_field_time_error') ?>');
                        return [0, 1439];
                    }
                    var result = new Array();

                    if (typeof(values[1]) != 'undefined' && values[1].length && typeof(values[2]) != 'undefined' && values[2].length)
                        result[0] = parseInt(values[1])*60 + parseInt(values[2]);
                    else
                        result[0] = iMin;

                    if (typeof(values[3]) != 'undefined' && values[3].length && typeof(values[4]) != 'undefined' && values[4].length)
                        result[1] = parseInt(values[3])*60 + parseInt(values[4]);
                    else
                        result[1] = iMax;

                    return result;
                };

                var onChange = function(e, ui) {
                    values = ui.values;
                    var from = parseInt(values[0]);
                    var from_h = Math.floor(from / 60);     if (from_h < 10) from_h = '0'+from_h;
                    var from_m = from % 60;                 if (from_m < 10) from_m = '0'+from_m;
                    var to = parseInt(values[1]);
                    var to_h = Math.floor(to / 60);         if (to_h < 10) to_h = '0'+to_h;
                    var to_m = to % 60;                     if (to_m < 10) to_m = '0'+to_m;
                    cur.val( from_h+':'+from_m + ' - ' + to_h+':'+to_m );
                };

                $slider.slider({
                    range: true,
                    min: iMin,
                    max: iMax,
                    step: parseFloat(cur.attr("step")) ? parseFloat(cur.attr("step")) : 1,
                    values: funcGetValues(cur),
                    change: onChange,
                    slide: onChange,
                    create: onCreateRange
                });

                cur.bind('change', function () {
                    var values = funcGetValues($(this));
                    $slider.slider("option", "values", values);
                });
            };
        </script>
<?php
        $sJsCode = ob_get_clean();
        $sInput = $this->genInputStandard($aInput);
        return str_replace('type="doublerange"', 'type="doublerange_time"', $sInput).$sJsCode;
    }
}

/** @} */
