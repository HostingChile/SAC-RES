var last_values = [5, 10];
var slider;

$(function () {
    slider = $('#slider')[0];
    var timeout_handle;
    var timeout = 1500;
    
    //Create slider
    noUiSlider.create(slider, {
        start: last_values,
        connect: true,
        step: 1,
        direction: 'rtl',
        range: {
            'min': 0,
            'max': 50
        },
        pips: {// Show a scale with the slider
            mode: 'values',
            values: [0, 13, 25, 38, 50],
            density: 30,
            format: {
                to: function (value) {
                    return moment().subtract(value, 'd').format('D/M/Y');
                }
            }
        }
    });

    //Update on slide
    slider.noUiSlider.on('update', function (values) {
        var start = values[1];
        var end = values[0];
        clearTimeout(timeout_handle);

        $('#slider-value-start').text(moment().subtract(start, 'd').format('D/M/Y'));
        $('#slider-value-end').text(moment().subtract(end, 'd').format('D/M/Y'));
    });

    //Request for selected dates
    slider.noUiSlider.on('change', function (values, handle) {
        if(last_values[0] == values[1] && last_values[1] == values[0]){return;}
        
        timeout_handle = setTimeout(function(){
            requestUpdate(values);
        },timeout);        
    });
});