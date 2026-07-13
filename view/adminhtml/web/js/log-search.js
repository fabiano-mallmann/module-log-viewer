/**
 * Copyright © Fsm contributors
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var DEBOUNCE_MS = 200;

    return function (config, element) {
        var $root = $(element),
            $input = $root.find('[data-role="log-search-input"]'),
            $count = $root.find('[data-role="log-search-count"]'),
            $prev = $root.find('[data-role="log-search-prev"]'),
            $next = $root.find('[data-role="log-search-next"]'),
            $clear = $root.find('[data-role="log-search-clear"]'),
            $content = $root.find('[data-role="log-content"]'),
            originalHtml = $content.html(),
            matches = $(),
            current = -1,
            debounceTimer = null,
            i18n = {
                noMatches: config.noMatches || 'No matches',
                ofLabel: config.ofLabel || '%1 of %2'
            };

        function escapeRegExp(value) {
            return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function clearSearch() {
            $content.html(originalHtml);
            matches = $();
            current = -1;
            $count.text('');
            $prev.prop('disabled', true);
            $next.prop('disabled', true);
        }

        function updateNav() {
            var hasMatches = matches.length > 0;

            $prev.prop('disabled', !hasMatches);
            $next.prop('disabled', !hasMatches);

            if (!hasMatches) {
                $count.text(i18n.noMatches);
                return;
            }

            $count.text(
                i18n.ofLabel
                    .replace('%1', String(current + 1))
                    .replace('%2', String(matches.length))
            );
        }

        function scrollToCurrent() {
            if (current < 0 || !matches[current]) {
                return;
            }

            matches.removeClass('is-current');
            $(matches[current]).addClass('is-current');
            matches[current].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function applySearch(term) {
            var query = $.trim(term || '');

            if (!query) {
                clearSearch();
                return;
            }

            var source = $('<div/>').html(originalHtml).text(),
                regex = new RegExp(escapeRegExp(query), 'gi'),
                html = $('<div/>').text(source).html().replace(regex, function (match) {
                    return '<mark class="fsm-logviewer-hit">' + match + '</mark>';
                });

            $content.html(html);
            matches = $content.find('mark.fsm-logviewer-hit');
            current = matches.length ? 0 : -1;
            updateNav();
            scrollToCurrent();
        }

        function go(delta) {
            if (!matches.length) {
                return;
            }

            current = (current + delta + matches.length) % matches.length;
            updateNav();
            scrollToCurrent();
        }

        $input.on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                applySearch($input.val());
            }, DEBOUNCE_MS);
        });

        $input.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                go(event.shiftKey ? -1 : 1);
            } else if (event.key === 'Escape') {
                $input.val('');
                clearSearch();
            }
        });

        $next.on('click', function () {
            go(1);
        });

        $prev.on('click', function () {
            go(-1);
        });

        $clear.on('click', function () {
            $input.val('');
            clearSearch();
            $input.trigger('focus');
        });

        clearSearch();
    };
});
