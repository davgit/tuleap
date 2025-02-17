(function () {
    angular
        .module('planning')
        .service('CardFieldsService', CardFieldsService);

    CardFieldsService.$inject = ['$sce'];

    function CardFieldsService($sce) {
        return {
            cardFieldIsSimpleValue      : cardFieldIsSimpleValue,
            cardFieldIsList             : cardFieldIsList,
            cardFieldIsText             : cardFieldIsText,
            cardFieldIsDate             : cardFieldIsDate,
            cardFieldIsFile             : cardFieldIsFile,
            cardFieldIsCross            : cardFieldIsCross,
            cardFieldIsPermissions      : cardFieldIsPermissions,
            cardFieldIsUser             : cardFieldIsUser,
            getCardFieldListValues      : getCardFieldListValues,
            getCardFieldTextValue       : getCardFieldTextValue,
            getCardFieldFileValue       : getCardFieldFileValue,
            getCardFieldCrossValue      : getCardFieldCrossValue,
            getCardFieldPermissionsValue: getCardFieldPermissionsValue,
            getCardFieldUserValue       : getCardFieldUserValue
        };

        function cardFieldIsSimpleValue(type) {
            switch (type) {
                case 'string':
                case 'int':
                case 'float':
                case 'aid':
                case 'atid':
                case 'computed':
                case 'priority':
                    return true;
            }
        }

        function cardFieldIsList(type) {
            switch (type) {
                case 'sb':
                case 'msb':
                case 'rb':
                case 'cb':
                case 'tbl':
                case 'shared':
                    return true;
            }
        }

        function cardFieldIsDate(type) {
            switch (type) {
                case 'date':
                case 'lud':
                case 'subon':
                    return true;
            }
        }

        function cardFieldIsText(type) {
            return type == 'text';
        }

        function cardFieldIsFile(type) {
            return type == 'file';
        }

        function cardFieldIsCross(type) {
            return type == 'cross';
        }

        function cardFieldIsPermissions(type) {
            return type == 'perm';
        }

        function cardFieldIsUser(type) {
            return type == 'subby';
        }

        function getCardFieldListValues(values) {
            function getValueRendered(value) {
                if (value.color) {
                    return getValueRenderedWithColor(value);
                } else if (value.avatar_url) {
                    return getCardFieldUserValue(value);
                }

                return value.label;
            }

            function getValueRenderedWithColor(value) {
                var rgb   = 'rgb(' + _.escape(value.color.r) + ', ' + _.escape(value.color.g) + ', ' + _.escape(value.color.b) + ')',
                    color = '<span class="color" style="background: ' + rgb + '"></span>';

                return color + _.escape(value.label);
            }

            return $sce.trustAsHtml(_.map(values, getValueRendered).join(', '));
        }

        function getCardFieldTextValue(value) {
            return $sce.trustAsHtml(_.escape(value));
        }

        function getCardFieldFileValue(artifact_id, field_id, file_descriptions) {
            function getFileUrl(file) {
                return '/plugins/tracker/?aid=' + artifact_id + '&field=' + field_id + '&func=show-attachment&attachment=' + file.id;
            }

            function getFileLink(file) {
                return '<a data-nodrag="true" href="' + getFileUrl(file) + '"><i class="icon-file-text-alt"></i> ' + _.escape(file.name) + '</a>';
            }

            return $sce.trustAsHtml(_.map(file_descriptions, getFileLink).join(', '));
        }

        function getCardFieldCrossValue(links) {
            function getCrossLink(link) {
                return $sce.trustAsHtml('<a data-nodrag="true" href="' + link.url + '">' + _.escape(link.ref) + '</a>');
            }

            return $sce.trustAsHtml(_.map(links, getCrossLink).join(', '));
        }

        function getCardFieldPermissionsValue(values) {
            return _(values).join(', ');
        }

        function getCardFieldUserValue(value) {
            var avatar = '<img src="' + value.avatar_url + '">';

            return '<div data-nodrag="true" class="user"><div class="avatar">' + avatar + '</div>' + value.link + '</div>';
        }
    }
})();
