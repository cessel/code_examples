import $ from 'jquery'

class BlockMap extends window.HTMLDivElement {
  constructor (...args) {
    const self = super(...args)
    self.init()
    return self
  }

  init () {
    this.$ = $(this)
    this.checkIfLoad = false
    this.mapContainerSelector = '.ksma-block-map'
    this.mapId = 'map';
    this.mapZoom = 14;
    this.mapJqElement = $("#" + this.mapId)
    this.default_lat = 45.015186;
    this.default_lng = 38.974478;

    this.ymap();
    $(window).scroll();
  }

  initMap = () => {

    let lat = this.mapJqElement.data('lat');
    let lng = this.mapJqElement.data('lng');

    var address = this.mapJqElement.data('address');
    var coords = { 'lat':lat, 'lng':lng };
    if (lat !== '' && lng !== '') {
      this.mapStart(coords);
    }
    else if( address !== '' ){
      ymaps.geocode(address).then((res) => {
        var position = res.geoObjects.get(0).geometry.getCoordinates();
        var coords = {'lat':position[0], 'lng':position[1], 'address' : address};
        this.coordsCache(coords);
        this.mapStart(coords);
      });
    } else{
      coords = { 'lat' : this.default_lat, 'lng' : this.default_lng };
      this.mapStart(coords);
    }
  }
  // Инициализация карты по переданныым координатам
  mapStart = (coords) => {
    let spinner = this.mapContainer.children('.ksma-block-map__loader')

    var myMapTemp = new ymaps.Map(this.mapId, {
      center: [coords.lat,coords.lng], // координаты центра на карте
      zoom: this.mapZoom, // коэффициент приближения карты
      controls: ['zoomControl', 'fullscreenControl'] // выбираем только те функции, которые необходимы при использовании
    }, {
      // Зададим опции для элементов управления.
      fullscreenControlFloat: 'left'
    });
    myMapTemp.behaviors.disable('scrollZoom');

    var MyIconContentLayout = ymaps.templateLayoutFactory.createClass(
      '<div style="color: #FFFFFF; font-weight: bold;"></div>'
    );

    var myPlacemark = new ymaps.Placemark(
      [coords.lat,coords.lng],{
      }, {
        iconLayout: 'default#imageWithContent',
        iconImageHref: '/wp-content/themes/ksma/assets/images/map_marker.svg',
        iconImageSize: [36, 36],
        iconImageOffset: [-18, -36],
        iconContentOffset: [15, 15],
        iconContentLayout: MyIconContentLayout
      }
    );

    myMapTemp.geoObjects.add(myPlacemark);

    // Получаем первый экземпляр коллекции слоев, потом первый слой коллекции
    var layer = myMapTemp.layers.get(0).get(0);

    // Решение по callback-у для определения полной загрузки карты
    this.waitForTilesLoad(layer).then(() => {
      // Скрываем индикатор загрузки после полной загрузки карты
      spinner.remove();
    });
  }


// Функция для определения полной загрузки карты (на самом деле проверяется загрузка тайлов)
  waitForTilesLoad = (layer) => {
    return new ymaps.vow.Promise((resolve) => {
      var tc = this.getTileContainer(layer), readyAll = true;
      tc.tiles.each(function (tile) {
        if (!tile.isReady()) {
          readyAll = false;
        }
      });
      if (readyAll) {
        resolve();
      } else {
        tc.events.once("ready", function() {
          resolve();
        });
      }
    });
  }
  getTileContainer = (layer) =>  {
    for (var k in layer) {
      if (layer.hasOwnProperty(k)) {
        if (
          layer[k] instanceof ymaps.layer.tileContainer.CanvasContainer
          || layer[k] instanceof ymaps.layer.tileContainer.DomContainer
        ) {
          // console.log(layer[k]);
          return layer[k];
        }
      }
    }
    return null;
  }
  loadScript = (url, callback) => {
    var script = document.createElement("script");

    if (script.readyState){  // IE
      script.onreadystatechange = function(){
        if (script.readyState === "loaded" ||
          script.readyState === "complete"){
          script.onreadystatechange = null;
          callback();
        }
      };
    } else {  // Другие браузеры
      script.onload = function(){
        callback();
      };
    }

    script.src = url;
    document.getElementsByTagName("head")[0].appendChild(script);
  }
  ymap = () => {
    var globalThis = this

    $(window).on('scroll',function() {
      // проверяем первый ли раз загружается Яндекс.Карта, если да, то загружаем
      if(!globalThis.checkIfLoad) {

        // проверка на докрутку до определенного элемента
        globalThis.mapContainer = $(globalThis.mapContainerSelector)
        var trigger_container = globalThis.mapContainer.offset().top;
        trigger_container = trigger_container - 1000;

        var current_offset = $(this).scrollTop();

        //если мы докрутили до нужного элемента
        if (current_offset > trigger_container) {

          // Чтобы не было повторной загрузки карты, мы изменяем значение переменной
          globalThis.checkIfLoad = true;

          // Показываем индикатор загрузки до тех пор, пока карта не загрузится
          var spinner = globalThis.mapContainer.children('.loader');
          spinner.addClass('is-active');

          // Загружаем API Яндекс.Карт
          globalThis.loadScript("https://api-maps.yandex.ru/2.1.79/?lang=ru_RU&apikey=cb9ffd09-7c36-4db8-868f-33517cc92c2f&loadByRequire=1", () => {
            // Как только API Яндекс.Карт загрузились, сразу формируем карту и помещаем в блок с идентификатором map
            ymaps.load(globalThis.initMap);
          });
        }
      }
    });

  }
  coordsCache(coords) {
    let mapContainer = $('.ksma-block-map');
    const ajax_url = mapContainer.data('admin_url')

    const action = 'set_map_preloader'
    const nonce = mapContainer.data('nonce')

    let data = {'action': action, '_ajax_nonce': nonce, 'data':coords}
    // console.log(data);
    
    $.ajax({
      url : ajax_url,
      data : data,
      method : 'POST', //Post method
      success : function( response ){
        // console.log(response)
      },
      error : function(error){
        // console.error(error)
      }
    })
  }

}

window.customElements.define('ksma-block-map', BlockMap, { extends: 'section' })
