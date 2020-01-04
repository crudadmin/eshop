<template>
    <div class="pricing">
        <div class="pricing-rows clearfix" v-for="(key, pricing) in pricings">
            <div class="form-group price-items">
                <div class="price-row">
                    <div class="left">
                        <label v-if="key == 0">Typ Ceníku</label>
                        <select class="form-control" :disabled="key == 0 || !canEditPrices" v-model="pricing.id">
                            <option value="" v-if="!pricing.id" selected>Vyberte cennik</option>
                            <option :value="option.id" :disabled="isDisabled(key, option.id)" v-for="option in pricings_rows">{{ option.name }}</option>
                        </select>
                    </div>
                    <div class="prices">
                        <div class="price-col">
                            <label v-if="key == 0">Cena</label>
                            <input type="number" :value="pricing.price" :disabled="!canEditPrices" @keyup="onChange(pricing, $event)" class="form-control">
                        </div>
                        <div class="price-col">
                            <label v-if="key == 0">DPH</label>
                            <input type="number" :value="taxSize(pricing.price)" disabled class="form-control">
                        </div>
                        <div class="price-col">
                            <label v-if="key == 0">Cena s DPH</label>
                            <input type="number" step=".2" :value="taxPrice(pricing.price)" :disabled="!canEditPrices" debounce="1000" @keyup="changePriceTax(pricing, $event)" class="form-control">
                        </div>
                    </div>
                    <div class="buttons">
                        <button type="button" class="btn btn-primary" data-toggle="tooltip" @click="showDiscounts(key)" title="Zobrazit konečnou cenu po slevě"><i class="fa fa-info"></i></button>
                        <button type="button" class="btn btn-danger" @click="removePricing(key)" v-if="canEditPrices && canRemovePricing(pricing)"><i class="fa fa-remove"></i></button>
                    </div>
                </div>

                <div class="discount-box" v-show="showpricing === key">
                    <div class="price-row" v-if="getPricing(pricing)">
                        <div class="left">
                            <span class="discount-title">Sleva na ({{ getPricing(pricing).name }})</span>
                        </div>
                        <div class="prices">
                            <div class="price-col">
                                <input type="number" :value="pricingPrice(pricing)" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxSize(pricingPrice(pricing))" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxPrice(pricingPrice(pricing))" disabled class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="price-row" v-if="inVariants">
                        <div class="left">
                            <span class="discount-title">Po uprave na variante</span>
                        </div>
                        <div class="prices">
                            <div class="price-col">
                                <input type="number" :value="priceAfterVariantPrice(pricing)" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxSize(priceAfterVariantPrice(pricing))" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxPrice(priceAfterVariantPrice(pricing))" disabled class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="price-row" v-if="inVariants">
                        <div class="left">
                            <span class="discount-title">Po slevě na variante</span>
                        </div>
                        <div class="prices">
                            <div class="price-col">
                                <input type="number" :value="priceAfterVariantDiscount(pricing)" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxSize(priceAfterVariantDiscount(pricing))" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxPrice(priceAfterVariantDiscount(pricing))" disabled class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="price-row">
                        <div class="left">
                            <span class="discount-title">Po slevě na produktu</span>
                        </div>
                        <div class="prices">
                            <div class="price-col">
                                <input type="number" :value="priceAfterProductDiscount(pricing)" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxSize(priceAfterProductDiscount(pricing))" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxPrice(priceAfterProductDiscount(pricing))" disabled class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="price-row" v-show="manufacturer">
                        <div class="left">
                            <span class="discount-title">Po slevě ({{ manufacturer ? manufacturer.name : '' }})</span>
                        </div>
                        <div class="prices">
                            <div class="price-col">
                                <input type="number" :value="manufacturerPrice(pricing)" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxSize(manufacturerPrice(pricing))" disabled class="form-control">
                            </div>

                            <div class="price-col">
                                <input type="number" :value="taxPrice(manufacturerPrice(pricing))" disabled class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" @click="newPricing" class="btn btn-success" v-if="pricings.length < pricings_rows.length && canEditPrices"><i class="fa fa-plus"></i> Nový Ceník</button>
        <input type="hidden" name="pricings" :value="getJSONPricings">
        <input type="hidden" name="price" :value="row.price||0">
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['key', 'field', 'row', 'model', 'history_changed'],

    data(){
        return {
            manufacturer : null,

            pricings_rows : [],
            pricings : [],
            showpricing : false,

            productrow : null,
            productmodel : null,
        }
    },

    ready(){
        this.loadPricings(function(){
            this.$watch('row.id', function(row){
                this.reloadPricings(row ? JSON.parse(this.row.pricings) : {});
            });

            //Receive parent row
            this.$dispatch('sendParentRow');

            this.onChangeVariantDiscountType();
        });
    },

    events : {
        //Receive product row
        getParentRow(data){
            if ( data.model.slug == 'products' ){
                this.productrow = data.row;
                this.productmodel = data.model;
            }
        },
    },

    computed: {
        getJSONPricings(){
            var pricings = _.cloneDeep(this.pricings).filter(function(item){
                var pricing = _.find(this.pricings_rows||{}, { id : item.id });

                if ( pricing && pricing.default === true )
                    return false;

                return true;
            }.bind(this));

            if ( this.inVariants && this.row.price_operator != 'abs' )
                pricings = [];

            var data = JSON.stringify(pricings);

            this.$set('model.fields.pricings.value', data);

            return data;
        },
        product(){
            if ( ! this.inVariants )
                return this.row;

            return this.productrow;
        },
        inVariants(){
            return this.model.slug != 'products';
        },
        tax(){
            var options = this.productmodel.fields.tax_id.options;

            for ( var i = 0; i < options.length; i++ )
            {
                if ( options[i][0] == this.product.tax_id )
                    return options[i][1].tax;
            }

            return 0;
        },
        manufacturer(){
            //Get manufacturer data
            var options = this.productmodel.fields.manufacturer_id.options;

            for ( var key in options )
                if ( options[key][0] == this.product.manufacturer_id ){
                    return options[key][1];
                }

            return null;
        },
        canEditPrices(){
            if ( this.inVariants && this.row.price_operator != 'abs' )
                return false;

            return true;
        }
    },

    methods: {
        /*
         * Default price
         */
        value(value){
            return value || 0;
        },
        taxSize(value){
            return (value * (this.tax / 100)).toFixed(2);
        },
        taxPrice(value){
            return (value * (1 + (this.tax / 100))).toFixed(2);
        },

        /*
         * Second levels of discounts
         */
        pricingPrice(orig_pricing){
            var pricing = this.getPricing(orig_pricing);

            if ( ! pricing )
                return 0;

            return this.operatorModifier(orig_pricing.price, pricing.price_operator, pricing.price||0);
        },
        priceAfterVariantPrice(orig_pricing){
            var price = this.pricingPrice(orig_pricing);

            if ( ! this.inVariants )
                return price;

            return this.operatorModifier(price, this.row.price_operator, this.row.price_value||0);
        },
        priceAfterVariantDiscount(orig_pricing){
            var price = this.priceAfterVariantPrice(orig_pricing);

            if ( ! this.inVariants || this.row.discount_operator == 'abs' )
                return price;

            return this.operatorModifier(price, this.row.discount_operator, this.row.discount||0);
        },
        priceAfterProductDiscount(orig_pricing){
            var price = this.priceAfterVariantDiscount(orig_pricing);

            return this.operatorModifier(price, this.product.discount_operator, this.product.discount||0);
        },
        manufacturerPrice(orig_pricing){
            var value = this.priceAfterProductDiscount(orig_pricing);

            if ( ! this.manufacturer )
                return value;

            return this.operatorModifier(value, this.manufacturer.discount_operator, this.manufacturer.discount||0);
        },
        isDisabled(key, id){
            for ( var k in this.pricings )
                if ( this.pricings[k].id === id && k != key ){
                    return true;
                }

            return false;
        },
        getPricing(pricing){
            return _.find(this.pricings_rows, { id : pricing.id });
        },
        onChangeVariantDiscountType(){
            if ( ! this.inVariants )
                return;

            this.$watch('row.price_operator', function(value){
                if ( value != 'abs' ){
                    this.updateDefaultPrice(0);
                    this.reloadPricings(JSON.parse(this.product.pricings||'[]'));
                }
            });

            this.$watch('product.price + product.pricings', function(){
                if ( this.row.price_operator != 'abs' ){
                    this.reloadPricings(JSON.parse(this.product.pricings||'[]'));
                }
            });
        },


        /*
         * Methods
         */
        reloadPricings(data){
            this.pricings = [{
                    id : _.find(this.pricings_rows, { default : true }).id,
                    price : (this.row.price_operator == 'abs' ? this.row.price : this.product.price) || 0,
            }].concat(data||[]);
        },
        loadPricings(callback){
            this.$http.get('get-pricings').then(function(response){
                this.pricings_rows = response.data;

                callback.call(this);

                var data = (this.inVariants && ! this.row.id) ? JSON.parse(this.product.pricings) : null;

                this.reloadPricings(data);
            });
        },
        newPricing(){
            this.pricings.push({
                id : null,
                price : 0,
            });
        },
        operatorModifier(value, operator, discount){
            value = parseFloat(value);
            discount = parseFloat(discount);

            if ( !operator ){
                return value;
            } else if ( operator == '+%' ){
                value = value * (1 + (discount / 100));
            } else if ( operator == '-%' ){
                return this.operatorModifier(value, '+%', -discount);
            } else if ( operator == '+' ){
                value += discount;
            } else if ( operator == '-'){
                value -= discount;
            } else if ( operator == '*'){
                value *= discount;
            } else if ( operator == 'abs' ){
                value = discount;
            }

            return value.toFixed(2);
        },
        showDiscounts(id){
            this.showpricing = this.showpricing === id ? false : id;
        },
        canRemovePricing(pricing){
            var pricing = this.getPricing(pricing);

            if ( !pricing || pricing.default !== true )
                return true;

            return false;
        },
        removePricing(key){
            this.pricings.splice(key, 1);
        },

        /*
         * Update events
         */
        onChange(pricing, e){
            pricing.price = e.target.value;

            this.updateDefaultPrice(pricing);
        },
        changePriceTax : _.debounce(function(pricing, e){
            pricing.price = (e.target.value / (1 + (this.tax / 100))).toFixed(2);

            this.updateDefaultPrice(pricing);
        }, 1000),
        updateDefaultPrice(pricing){
            if ( pricing === 0 )
                return this.$set('row.price', 0);

            if ( _.find(this.pricings_rows, { id : pricing.id }).default === true ){
                this.$set('row.price', pricing.price);
            }
        },
    }
}
</script>