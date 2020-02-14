
 /* CS-Cart Addon Manage Markup
 * @category   Add-ons
 * @copyright  Copyright (c) by Alexey Bituganov (ailands@ya.ru)
 * @license    MIT License
*/

(function(_, $){

    $(function(){
        $(_.doc).on('click', '#mmu_add_rule', function(event){
            var elm = $(event.target),
        new_rule = '<tr id="mmu-index-{index}">\
                <td>\
                    <input type="hidden" name="category_data[manage_markup][{index}][id]" value="">\
                    <input type="text"  mmu-input="price-from" class="cm-value-decimal" name="category_data[manage_markup][{index}][price_from]" value="0" maxlength="13"></td>\
                <td>\
                    <input type="text" mmu-input="price-to" class="cm-value-decimal" name="category_data[manage_markup][{index}][price_to]" value="0" maxlength="13"></td>\
                <td>\
                    <input type="text" mmu-input="procent" class="cm-value-decimal" name="category_data[manage_markup][{index}][procent]" value="0" maxlength="5"></td>\
                <td>\
                    <input type="text" mmu-input="margin" class="cm-value-decimal" name="category_data[manage_markup][{index}][margin]" value="0" maxlength="13"></td>\
                <td>\
                    <a  mmu-index="{index}" class="btn">X</a></td>\
            </tr>',
            rules = $('#manage_markup_rules');
            index = $('#mmu_index').val();
            index = parseInt(index , 10 ) + 1;
            $('#mmu_index').val(index);
            new_rule = new_rule.replace(/\{index\}/g, index.toString());
            rules.append(new_rule)
            return false;
        });
        
        $(_.doc).on('click', '#manage_markup_categories_hook table a.btn', function(event){
            var elm = $(event.target);
            $('#manage_markup_categories_hook table tr').remove('#mmu-index-{index}'.replace('{index}',elm.attr("mmu-index")));
            return false;
        });
        
        $(_.doc).on('change', '#manage_markup_categories_hook table input', function(event){
            var elm = $(event.target),
                value = '',
                result = '',
                check_elm = function(value){
                    var reg = / /,
                        error = '';
                    switch (elm.attr('mmu-input')){
                        case 'price-from':
                            reg = /^([0-9]+)([\,\.]{0,1})([0-9]{0,2})$/;
                            value = value.match(reg);
                            if(value){
                                if(parseFloat(value[1]+"."+value[3]) > 9999999999){
                                    error = error + ' too many value';
                                    value = false;
                                }                                
                            }
                            else{
                                error = ' format: 9999999999.99';
                            }
                            break;
                        case 'price-to':
                            reg = /^([0-9]+)([\,\.]{0,1})([0-9]{0,2})$/;
                            value = value.match(reg);
                            if(value){
                                if(parseFloat(value[1]+"."+value[3]) > 9999999999){
                                    error = error + ' too many value';
                                    value = false;
                                }                                
                            }
                            else{
                                error = ' 9999999999.99';
                            }
                            break;
                        case 'procent':
                            reg = /^([0-9]{1,3})([\,\.]{0,1})([0-9]{0,2})$/;
                            value = value.match(reg);
                            console.log(value);
                            if(value){
                                if(!value[2]){
                                    value[2] = ".";
                                }
                                if(parseFloat(value[1]+"."+value[3]) > 100){
                                    error = error + ' value > 100%, format: 99.99';
                                    value = false;
                                }                                
                            }
                            else{
                                error = ' format: 99.99';
                            }
                            break;
                        case 'margin':
                            reg = /^([0-9]+)([\,\.]{0,1})([0-9]{0,2})$/;
                            value = value.match(reg);
                            if(value){
                                if(parseFloat(value[1]+"."+value[3]) > 9999999999){
                                    error = error + ' too many value';
                                    value = false;
                                }                                
                            }
                            else{
                                error = ' format: 9999999999.99';
                            }
                            break;
                    }
                    if(!value){
                        alert('Wrong!' + error);
                        elm.addClass("cm-failed-field");
                    }
                    else{
                        elm.removeClass("cm-failed-field");
                    };
                    return value               
                };
                value = check_elm(elm.val().replace(/^\s+|\s+$/g, ''));
                if(value){
                    result = parseInt(value[1]).toString()+"."+(value[3] ? value[3] : "0");
                }
                else{
                    result = "0";
                }
                elm.val(result)
                return false; 
        });
        
        $(_.doc).on('focusin', '#manage_markup_categories_hook table input', function(event){
            var elm = $(event.target);
            value = elm.val().replace(/^\s+|\s+$/g, '');
            if(value == "0"){
                elm.val("");
            }
        });
        $(_.doc).on('focusout', '#manage_markup_categories_hook table input', function(event){
            var elm = $(event.target);
            value = elm.val().replace(/^\s+|\s+$/g, '');
            if(value == ""){
                elm.val("0");
            }
        });
        
        $.ceEvent('on', 'ce.update_object_status_callback', function(data, param) {
            if(data.update_ids !== undefined){
                if(data.update_status == "D"){
                    $(".mmu-id-"+data.update_ids).css("background","#f5f5f5");
                }
                else{
                    $(".mmu-id-"+data.update_ids).css("background","none");
                }
            }
        });
    });

})(Tygh, Tygh.$);

function fn_manage_markup_update_c_status(elem){
    var $ = Tygh.$;
    var group_id = $(elem).attr('name').match(/^[^\_]+\_([a-zа-я0-9]+)$/i)[1];
    if(group_id != undefined){
        $.ceAjax('request', fn_url('manage_markup.update_c_status'), {
            data:{
                category_id : group_id,
                c_status : $(elem).prop('checked'),
                result_ids: $(elem).attr('name')+",",
            },
            method: 'POST'
        });
    }          
}