'use strict';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:channelUploader
 * @description
 * # channelUploader
 */
angular.module('cutvApiAdminApp')
    .directive('channelImageUploader', function ($templateRequest, $compile) {
        return {
            restrict: 'E',
            replace: true,
            template: '<div class="flex vertical column uploader-content"><div class="ui active inverted dimmer"><div class="ui loader"></div></div></div>',
            scope: true,
            link: function(scope, element) {

                if (!_.isNil(scope.$flow)) {
                    console.log(scope.$flow);
                    $templateRequest('/wp-content/plugins/cutv-api/app/templates/upload-channel-image.html').then(function(html){
                        var template = angular.element(html);
                        element.html(template);
                        $compile(template)(scope);
                    });
                }

                scope.$watch();
            }
        };
    });
