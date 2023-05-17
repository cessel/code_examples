import $ from 'jquery'

class ListSearchResults extends window.HTMLDivElement {
  constructor (...args) {
    const self = super(...args)
    self.init()
    return self
  }

  init () {
    this.$ = $(this)
    this.radioSwitcher()
  }
  radioSwitcher = () => {

    $('.ksma-search-form__selectors-label').on('click',function (e) {
      let $currentRadioElement = $(this);
      let $allRadioElements = $('.ksma-search-form__selectors-label');

      $allRadioElements.removeClass('ksma-search-form__selectors-label--active');
      $allRadioElements.find('.ksma-search-form__selectors-radio').prop('checked',false);

      $currentRadioElement.addClass('ksma-search-form__selectors-label--active')
      $currentRadioElement.find('.ksma-search-form__selectors-radio').prop('checked',true);
    });
  }
}

window.customElements.define('ksma-list-search-results', ListSearchResults, { extends: 'section' })
