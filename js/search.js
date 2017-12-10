if (!window.jQuery) {
    console.warn('Search page functionality requires jQuery to be loaded first. Filter and sorting will not be functional');
} else {
    (function ($) {
        $('#applyFilters').on('click', function () {
            var filterString = [];

            $('.searchFilters').each(function () {
                if ($(this).is(':checked')) {
                    filterString.push($(this).val())
                }
            });

            filterString = filterString.join(',');
            var searchParams = buildSearchParams();
            searchParams.filter = filterString;

            window.location = '/search/?' + $.param(searchParams)
        });

        $('.search-result').on('click', function () {
            window.location = $(this).attr('data-link');
        });

        $('#searchSort').on('change', function () {
            var searchParams = buildSearchParams();
            searchParams.sort = $(this).val();

            window.location = '/search/?' + $.param(searchParams)
        });
    })(jQuery);

    function buildSearchParams() {
        var q = getParameterByName('q');
        var filter = getParameterByName('filter');
        var sort = getParameterByName('sort');

        var result = {
            q: q
        };

        if (filter && filter.length > 1) {
            result.filter = filter
        }

        if (sort) {
            result.sort = sort
        }

        return result;
    }

    function getParameterByName(name, url) {
        if (!url) {
            url = window.location.href;
        }
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }
}