var xlwcfg_app = {};
xlwcfg_app.Helpers = {};
xlwcfg_app.Views = {};
xlwcfg_app.Events = {};

_.extend(xlwcfg_app.Events, Backbone.Events);


xlwcfg_app.Helpers.uniqid = function (prefix, more_entropy) {

    if (typeof prefix == 'undefined') {
        prefix = "";
    }

    var retId;
    var formatSeed = function (seed, reqWidth) {
        seed = parseInt(seed, 10).toString(16); // to hex str
        if (reqWidth < seed.length) { // so long we split
            return seed.slice(seed.length - reqWidth);
        }
        if (reqWidth > seed.length) { // so short we pad
            return Array(1 + (reqWidth - seed.length)).join('0') + seed;
        }
        return seed;
    };

    // BEGIN REDUNDANT
    if (!this.php_js) {
        this.php_js = {};
    }
    // END REDUNDANT
    if (!this.php_js.uniqidSeed) { // init seed with big random int
        this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
    }
    this.php_js.uniqidSeed++;

    retId = prefix; // start with prefix, add current milliseconds hex string
    retId += formatSeed(parseInt(new Date().getTime() / 1000, 10), 8);
    retId += formatSeed(this.php_js.uniqidSeed, 5); // add seed hex string
    if (more_entropy) {
        // for more entropy we add a float lower to 10
        retId += (Math.random() * 10).toFixed(8).toString();
    }

    return retId;

};


jQuery(function ($) {
    // if (!(typeof pagenow !== "undefined" && pagenow === "xlwcfg_thankyou")) {
    //     return;
    // }
    $('#xlwcfg_settings_location').change(function () {
        if ($(this).val() == 'custom:custom') {
            $('.xlwcfg-settings-custom').show();
        } else {
            $('.xlwcfg-settings-custom').hide();
        }
    });

    $('#xlwcfg_settings_location').trigger('change');

    // Ajax Chosen Product Selectors
    var bind_ajax_chosen = function () {

        $(".xlwcfg-date-picker-field").datepicker({
            dateFormat: "yy-mm-dd",
            numberOfMonths: 1,
            showButtonPanel: true,
            beforeShow: function (input, inst) {
                $(inst.dpDiv).addClass('xl-datepickers');
            }
        });
        $(".xlwcfg-time-picker-field").mask("99 : 99");
        $('select.chosen_select').xlChosen();


        $("select.ajax_chosen_select_products").xlAjaxChosen({
            method: 'GET',
            url: XLWCFGParams.ajax_url,
            dataType: 'json',
            afterTypeDelay: 100,
            data: {
                action: 'woocommerce_json_search_products_and_variations',
                security: XLWCFGParams.search_products_nonce
            }
        }, function (data) {

            var terms = {};

            $.each(data, function (i, val) {
                terms[i] = val;
            });

            return terms;
        });


        $("select.ajax_chosen_select").each(function (element) {
            $(element).xlAjaxChosen({
                method: 'GET',
                url: XLWCFGParams.ajax_url,
                dataType: 'json',
                afterTypeDelay: 100,
                data: {
                    action: 'xlwcfg_json_search',
                    method: $(element).data('method'),
                    security: XLWCFGParams.ajax_chosen
                }
            }, function (data) {

                var terms = {};

                $.each(data, function (i, val) {
                    terms[i] = val;
                });

                return terms;
            });
        });

    };

    bind_ajax_chosen();

    //Note - this section will eventually be refactored into the backbone views themselves.  For now, this is more efficent. 
    $('.xlwcfg_rules_common').on('change', 'select.rule_type', function () {


        // vars
        var tr = $(this).closest('tr');
        var rule_id = tr.data('ruleid');
        var group_id = tr.closest('table').data('groupid');

        var ajax_data = {
            action: "xlwcfg_change_rule_type",
            security: XLWCFGParams.ajax_nonce,
            rule_category: $(this).parents(".xlwcfg-rules-builder").eq(0).attr('data-category'),
            group_id: group_id,
            rule_id: rule_id,
            rule_type: $(this).val()
        };

        tr.find('td.condition').html('').remove();
        tr.find('td.operator').html('').remove();

        tr.find('td.loading').show();
        tr.find('td.rule-type select').prop("disabled", true);
        // load location html
        $.ajax({
            url: ajaxurl,
            data: ajax_data,
            type: 'post',
            dataType: 'html',
            success: function (html) {
                tr.find('td.loading').hide().before(html);
                tr.find('td.rule-type select').prop("disabled", false);
                bind_ajax_chosen();
            }
        });
    });

    //Backbone views to manage the UX.
    var xlwcfg_Rule_Builder = Backbone.View.extend({
        groupCount: 0,
        el: '.xlwcfg-rules-builder[data-category="basic"]',
        events: {
            'click .xlwcfg-add-rule-group': 'addRuleGroup',
        },
        render: function () {

            this.$target = this.$('.xlwcfg-rule-group-target');
            this.category = 'basic';
            xlwcfg_app.Events.bind('xlwcfg:remove-rule-group', this.removeRuleGroup, this);

            this.views = {};
            var groups = this.$('div.xlwcfg-rule-group-container');
            _.each(groups, function (group) {
                this.groupCount++;
                var id = $(group).data('groupid');
                var view = new xlwcfg_Rule_Group(
                    {
                        el: group,
                        model: new Backbone.Model(
                            {
                                groupId: id,
                                groupCount: this.groupCount,
                                headerText: this.groupCount > 1 ? XLWCFGParams.text_or : XLWCFGParams.text_apply_when,
                                removeText: XLWCFGParams.remove_text,
                                category: this.category,
                            })
                    });

                this.views[id] = view;
                view.bind('xlwcfg:remove-rule-group', this.removeRuleGroup, this);

            }, this);

            if (this.groupCount > 0) {
                $('.rules_or').show();
            }
        },
        addRuleGroup: function (event) {
            event.preventDefault();

            var newId = 'group' + xlwcfg_app.Helpers.uniqid();
            this.groupCount++;

            var view = new xlwcfg_Rule_Group({
                model: new Backbone.Model({
                    groupId: newId,
                    groupCount: this.groupCount,
                    headerText: this.groupCount > 1 ? XLWCFGParams.text_or : XLWCFGParams.text_apply_when,
                    removeText: XLWCFGParams.remove_text,
                    category: this.category,
                })
            });

            this.$target.append(view.render().el);
            this.views[newId] = view;

            view.bind('xlwcfg:remove-rule-group', this.removeRuleGroup, this);

            if (this.groupCount > 0) {
                $('.rules_or').show();
            }

            bind_ajax_chosen();

            return false;
        },
        removeRuleGroup: function (sender) {

            delete(this.views[sender.model.get('groupId')]);
            sender.remove();
        }
    });

    //Backbone views to manage the UX.
    var xlwcfg_Rule_Builder2 = Backbone.View.extend({
        groupCount: 0,
        el: '.xlwcfg-rules-builder[data-category="product"]',
        events: {
            'click .xlwcfg-add-rule-group': 'addRuleGroup',
        },
        render: function () {

            this.$target = this.$('.xlwcfg-rule-group-target');
            this.category = 'product';
            xlwcfg_app.Events.bind('xlwcfg:remove-rule-group', this.removeRuleGroup, this);

            this.views = {};
            var groups = this.$('div.xlwcfg-rule-group-container');
            _.each(groups, function (group) {
                this.groupCount++;
                var id = $(group).data('groupid');
                var view = new xlwcfg_Rule_Group(
                    {
                        el: group,
                        model: new Backbone.Model(
                            {
                                groupId: id,
                                groupCount: this.groupCount,
                                headerText: this.groupCount > 1 ? XLWCFGParams.text_or : XLWCFGParams.text_apply_when,
                                removeText: XLWCFGParams.remove_text,
                                category: this.category,
                            })
                    });

                this.views[id] = view;
                view.bind('xlwcfg:remove-rule-group', this.removeRuleGroup, this);

            }, this);

            if (this.groupCount > 0) {
                $('.rules_or').show();
            }
        },
        addRuleGroup: function (event) {
            event.preventDefault();

            var newId = 'group' + xlwcfg_app.Helpers.uniqid();
            this.groupCount++;

            var view = new xlwcfg_Rule_Group({
                model: new Backbone.Model({
                    groupId: newId,
                    groupCount: this.groupCount,
                    headerText: this.groupCount > 1 ? XLWCFGParams.text_or : XLWCFGParams.text_apply_when,
                    removeText: XLWCFGParams.remove_text,
                    category: this.category,
                })
            });

            this.$target.append(view.render().el);
            this.views[newId] = view;

            view.bind('xlwcfg:remove-rule-group', this.removeRuleGroup, this);

            if (this.groupCount > 0) {
                $('.rules_or').show();
            }

            bind_ajax_chosen();

            return false;
        },
        removeRuleGroup: function (sender) {

            delete(this.views[sender.model.get('groupId')]);
            sender.remove();
        }
    });

    var xlwcfg_Rule_Group = Backbone.View.extend({
        tagName: 'div',
        className: 'xlwcfg-rule-group-container',
        template: _.template('<div class="xlwcfg-rule-group-header"><h4><%= headerText %></h4><a href="#" class="xlwcfg-remove-rule-group button"><%= removeText %></a></div><table class="xlwcfg-rules" data-groupid="<%= groupId %>"><tbody></tbody></table>'),
        events: {
            'click .xlwcfg-remove-rule-group': 'onRemoveGroupClick'
        },
        initialize: function () {
            this.views = {};
            this.$rows = this.$el.find('table.xlwcfg-rules tbody');

            var rules = this.$('tr.xlwcfg-rule');
            _.each(rules, function (rule) {
                var id = $(rule).data('ruleid');
                var view = new xlwcfg_Rule_Item(
                    {
                        el: rule,
                        model: new Backbone.Model({
                            groupId: this.model.get('groupId'),
                            ruleId: id,
                            category: this.model.get('category'),
                        })
                    });

                view.delegateEvents();

                view.bind('xlwcfg:add-rule', this.onAddRule, this);
                view.bind('xlwcfg:remove-rule', this.onRemoveRule, this);

                this.views.ruleId = view;

            }, this);
        },
        render: function () {

            this.$el.html(this.template(this.model.toJSON()));

            this.$rows = this.$el.find('table.xlwcfg-rules tbody');
            this.$el.attr('data-groupid', this.model.get('groupId'));

            this.onAddRule(null);

            return this;
        },
        onAddRule: function (sender) {
            var newId = 'rule' + xlwcfg_app.Helpers.uniqid();

            var view = new xlwcfg_Rule_Item({
                model: new Backbone.Model({
                    groupId: this.model.get('groupId'),
                    ruleId: newId,
                    category: this.model.get('category')
                })
            });

            if (sender == null) {
                this.$rows.append(view.render().el);
            } else {
                sender.$el.after(view.render().el);
            }
            view.bind('xlwcfg:add-rule', this.onAddRule, this);
            view.bind('xlwcfg:remove-rule', this.onRemoveRule, this);

            bind_ajax_chosen();

            this.views.ruleId = view;
        },
        onRemoveRule: function (sender) {

            var ruleId = sender.model.get('ruleId');
            const rulesgrp = sender.model.get('groupId');
            const cat = sender.model.get('category');
            var countRules = $(".xlwcfg-rules-builder[data-category='" + cat + "'] .xlwcfg_rules_common .xlwcfg-rule-group-container table tr.xlwcfg-rule").length;

            if (countRules == 1) {
                return;
            }
            delete(this.views[ruleId]);
            sender.remove();


            if ($("table[data-groupid='" + this.model.get('groupId') + "'] tbody tr").length == 0) {
                xlwcfg_app.Events.trigger('xlwcfg:removing-rule-group', this);

                this.trigger('xlwcfg:remove-rule-group', this);
            }
        },
        onRemoveGroupClick: function (event) {
            console.log('clicjed');
            event.preventDefault();
            xlwcfg_app.Events.trigger('xlwcfg:removing-rule-group', this);
            this.trigger('xlwcfg:remove-rule-group', this);
            return false;
        }
    });

    var xlwcfg_Rule_Item = Backbone.View.extend({
        tagName: 'tr',
        className: 'xlwcfg-rule',
        events: {
            'click .xlwcfg-add-rule': 'onAddClick',
            'click .xlwcfg-remove-rule': 'onRemoveClick'
        },
        render: function () {
            const base = this.model.get('category');

            const html = $('#xlwcfg-rule-template-' + base).html();
            const template = _.template(html);
            this.$el.html(template(this.model.toJSON()));
            this.$el.attr('data-ruleid', this.model.get('ruleId'));
            return this;
        },
        onAddClick: function (event) {
            event.preventDefault();

            xlwcfg_app.Events.trigger('xlwcfg:adding-rule', this);
            this.trigger('xlwcfg:add-rule', this);

            return false;
        },
        onRemoveClick: function (event) {
            event.preventDefault();

            xlwcfg_app.Events.trigger('xlwcfg:removing-rule', this);
            this.trigger('xlwcfg:remove-rule', this);

            return false;
        }
    });

    var ruleBuilder = new xlwcfg_Rule_Builder();
    ruleBuilder.render();
    var ruleBuilder2 = new xlwcfg_Rule_Builder2();
    ruleBuilder2.render();


});