/**
 * Admin Client Detail - UI layout and tab behaviors
 */
'use strict';

(function() {
    jQuery(document).ready(function($){
        // Ensure Activities tab is active when filter parameters are present
        // This fixes the issue where filters are applied but tab doesn't show
        var urlParams = new URLSearchParams(window.location.search);
        var hasFilters = urlParams.has('keyword') ||
                         (urlParams.has('activity_type') && urlParams.get('activity_type') !== 'all') ||
                         urlParams.has('date_from') ||
                         urlParams.has('date_to');
        var tabParam = urlParams.get('tab');

        // If filters are present and tab is empty/not set, activate Activities tab.
        // Honor the server-selected URL tab so /accounts?keyword= does not reveal an empty Activities pane.
        if (hasFilters && (!tabParam || tabParam === '')) {
            var serverTab = ($('#client_tabs').attr('data-active-tab') || 'activities');
            if (serverTab === 'notestrm') {
                serverTab = 'noteterm';
            }
            if (serverTab === 'activities') {
                var activitiesTab = $('#activities-tab');
                var activitiesPane = $('#activities');

                // Remove active class from all tabs and panes
                $('#client_tabs .nav-link').removeClass('active');
                $('#clientContent .tab-pane').removeClass('show active');

                // Activate Activities tab and pane
                activitiesTab.addClass('active').attr('aria-selected', 'true');
                activitiesPane.addClass('show active');
            }
        }

        // Function to handle personal-details-container visibility based on active tab
        function handlePersonalDetailsVisibility() {
            // Get the currently active tab
            var activeTab = $('#client_tabs .nav-link.active');
            var targetHref = activeTab.attr('href') || '';

            // Document-related tabs and full-width tabs (hide personal-details-container)
            var documentTabs = ['#alldocuments', '#notuseddocuments', '#email-v2'];

            if (documentTabs.indexOf(targetHref) !== -1) {
                // Hide personal-details-container for document tabs (right_section takes full width)
                $('.personal-details-container').hide();
            } else {
                // Show personal-details-container for other tabs (right_section takes remaining space)
                $('.personal-details-container').show();
            }
        }

        // Set initial visibility on page load based on active tab
        // Use setTimeout to ensure DOM is fully ready and active tab is set
        setTimeout(function() {
            handlePersonalDetailsVisibility();
            if ($('#accounts-tab').hasClass('active') && $('.popuploader').length && $('.popuploader').is(':visible')) {
                $('.popuploader').hide();
            }
        }, 50);

        // Tab click handler - update visibility when tabs are clicked
        $(document).on('click', '#client_tabs a', function(){
            // Use setTimeout to ensure Bootstrap tab switching completes first
            setTimeout(function() {
                handlePersonalDetailsVisibility();
            }, 10);
        });

        // Lazy-load hidden tab panes on first show (shells are always in the DOM).
        var loadedTabs = {};
        var initialTab = ($('#client_tabs').attr('data-active-tab') || 'activities');
        if (initialTab === 'notestrm') {
            initialTab = 'noteterm';
        }
        loadedTabs[initialTab] = true;

        function loadInvoicesForClientDetail() {
            var clientId = (typeof App !== 'undefined' && App.getPageConfig)
                ? App.getPageConfig('clientId')
                : (window.PageConfig && PageConfig.clientId);
            var siteUrl = '';
            if (typeof App !== 'undefined' && App.getUrl) {
                siteUrl = App.getUrl('siteUrl') || '';
            } else if (window.AppConfig && AppConfig.siteUrl) {
                siteUrl = AppConfig.siteUrl;
            }
            if (!clientId) {
                return;
            }
            if ($('.popuploader').length) {
                $('.popuploader').show();
            }
            $.ajax({
                url: String(siteUrl).replace(/\/$/, '') + '/get-invoices',
                type: 'GET',
                data: { clientid: clientId },
                success: function (html) {
                    if ($('.popuploader').length) {
                        $('.popuploader').hide();
                    }
                    $('.invoicedatalist').html(html);
                },
                error: function () {
                    if ($('.popuploader').length) {
                        $('.popuploader').hide();
                    }
                }
            });
        }

        function loadHiddenTab(tabValue) {
            var key = tabValue === 'notestrm' ? 'noteterm' : tabValue;
            if (!key || loadedTabs[key]) {
                return;
            }
            loadedTabs[key] = true;

            if (key === 'activities' && typeof getallactivities === 'function') {
                getallactivities();
            } else if (key === 'noteterm' && typeof getallnotes === 'function') {
                getallnotes();
            } else if (key === 'accounts') {
                loadInvoicesForClientDetail();
            }
        }

        $('#client_tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
            handlePersonalDetailsVisibility();
            var tabValue = $(this).attr('data-tab');
            var key = tabValue === 'notestrm' ? 'noteterm' : tabValue;
            var alreadyLoaded = !!loadedTabs[key];
            if (alreadyLoaded && ($(this).attr('href') || '') === '#accounts' && $('.popuploader').length && $('.popuploader').is(':visible')) {
                $('.popuploader').hide();
            }
            loadHiddenTab(tabValue);
        });

        // Layout: adjust add_note width on sidebar toggle
        $('.collapse-btn').click(function(){
            var windowsize = $(window).width();
            if($('.main-sidebar').width() == 65){
                if(windowsize > 2000){
                    $('.add_note').css('width','980px');
                } else {
                    $('.add_note').css('width','155px');
                }
            } else if($('.main-sidebar').width() == 250) {
                if(windowsize > 2000){
                    $('.add_note').css('width','1040px');
                } else {
                    $('.add_note').css('width','215px');
                }
            }
        });

        // Initial add_note width for very large screens
        var windowsize = $(window).width();
        if(windowsize > 2000){
            $('.add_note').css('width','980px');
        }

        // Set height of right side section
        var left_upper_height = $('.left_section_upper').height();
        var left_section_lower = $('.left_section_lower').height();
        var total_left  = left_upper_height + left_section_lower;
        total_left = total_left + 25;

        var right_section_height = $('.right_section').height();

        if(right_section_height > total_left ){
            var total_left_px = total_left + 'px';
            $('.right_section').css({"maxHeight": total_left_px});
            $('.right_section').css({"overflow": 'scroll' });
        } else {
            var total_left_px = total_left + 'px';
            $('.right_section').css({"maxHeight": total_left_px});
        }

        let css_property = {
            "display": "none",
        };
        $('#create_note_d').hide();
        $('.main-footer').css(css_property);
    });
})();
