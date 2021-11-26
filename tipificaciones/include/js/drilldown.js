$.widget( "hosting_desarrollo.DrillDown", {
	options:{
		list: [],
		unselectOnNavigate: false,
		unselectOnSearch: false,
		selectedId: null,
		breadcrumbStart: "Inicio",
		searchPlaceholder: "Buscar",
		breadcrumbSearchResults: "Resultados de la Búsqueda",
		noSearchResults: "No hay resultados"
	},
	_variables:{
		timer: null,
		searchDelay: 500
	},
	_create: function() {
		var _this = this;
		
		//Creating the HTML layout
		_this.element.append('<div class="header"><span class="dd-breadcrumb"><span class="breadcrumb-item" data-id="0">'+this.options.breadcrumbStart+'</span></span></div>');
		_this.element.append('<div class="items list-group"></div>');
		_this.element.append('<div class="search"><div class="input-group"><input type="text" class="form-control input-sm" placeholder="'+this.options.searchPlaceholder+'" aria-describedby="remove-glyph" /><span class="input-group-addon" id="remove-glyph"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></span></div></div>');
    
		//--------------------
		//Subscribing to events
		//--------------------
		
		//Clicking on an item
		_this.element.find('.items').on('click','.item',function(){
			//Clicking on an already selected item
			if($(this).hasClass('selected')){
				$(this).removeClass('selected');
				_this.options.selectedId = null;
				_this._trigger('onUnselect');
				return;
			}
			
			//Drill down on item
			if($(this).find('.badge').length){
				//Unselect if option is enabled
				if(_this.options.unselectOnNavigate && _this.options.selectedId){
					_this.options.selectedId = null;
					_this._trigger('onUnselect');
				}
				
				var title = $(this).find('.title').text();
				var id = $(this).data('id');
				var $breadcrumb_item = $('<span class="breadcrumb-item">'+title+'</span>');
				$breadcrumb_item.data('id',id);
				_this.element.find('.dd-breadcrumb').append($breadcrumb_item);       
				_this._renderList(_this._getObjects(_this.options.list, 'id', id, true)[0].children);       
			}
			//Select item
			else{
				_this.element.find('.items .list-group-item.selected').removeClass('selected');
				$(this).addClass('selected');
				_this.options.selectedId = $(this).data('id');
				_this._trigger('onSelect', null, {sel: $(this)});
			}
		});
		
		//Clicking on a breadcrumb item
		_this.element.find('.dd-breadcrumb').on('click', '.breadcrumb-item', function(){
			//Do nothing if it is las elemennt
			if($(this).is(':last-child')) return;
			
			//If it is the search title, show search results
			if($(this).hasClass('search_title')){
				_this._search(_this);
				return;
			}
			
			//Unselect if option is enabled
			if(_this.options.unselectOnNavigate && _this.options.selectedId){
				_this.options.selectedId = null;
				_this._trigger('onUnselect');
			}
			
			//Remove elements right to the clicked onw
			$(this).nextAll('.breadcrumb-item').remove();
			var id = $(this).data('id');
			
			//Render initial list
			if(id == 0){
				_this._renderList(_this.options.list);
			}
			//Render specific list
			else{
				_this._renderList(_this._getObjects(_this.options.list, 'id', id, true)[0].children);
			}
		});
		
		//Typing on the search input or and clicking on the remove glyphicon
		_this.element.find('.search').on('keyup', 'input', function(){
			_this._search(_this);
		}).on('click','#remove-glyph',function(){
			$(this).siblings('input').val('');
			if(_this.element.find('.dd-breadcrumb .breadcrumb-item').last().text() != _this.options.breadcrumbStart){
				//Unselect if option is enabled
				if(_this.options.unselectOnSearch && _this.options.selectedId){
					_this.options.selectedId = null;
					_this._trigger('onUnselect');
				}
				
				var $bc_start = $('<span class="breadcrumb-item">'+_this.options.breadcrumbStart+'</span>');
				$bc_start.data('id',0);
				_this.element.find('.dd-breadcrumb').html($bc_start);
				_this._renderList(_this.options.list);
			}
		});
	
		//Render the initial list
		_this._renderList(_this.options.list);
	},
	//Helper to search objects based on the key value pair. Optionally can define exact search or not
	_getObjects: function(obj, key, val, exact) {
		var objects = [];
		var exact = typeof exact == 'undefined' ? true : exact;
		
		for (var i in obj) {
			if (!obj.hasOwnProperty(i)) continue;
			if (typeof obj[i] == 'object') {
				objects = objects.concat(this._getObjects(obj[i], key, val, exact));
			} 
			else if ((i == key && obj[key] == val) || (!exact && i == key && obj[key].toString().toLowerCase().indexOf(val) != -1 )) {	
				objects.push(obj);
			}
		}		
		return objects;
	},
	//Returns a new .item based on the obj information
	_newItem: function(obj){
		var $item = $('<span class="list-group-item item"><span class="title">'+obj.title+'</span></span>');
		$item.data('id', obj.id);
		if(obj.children){
			$item.append('<span class="badge"><span class="glyphicon glyphicon-menu-right" aria-hidden="true"></span></span>');
		}
		return $item;
	},
	//Clears the items and renders teh new ones
	_renderList: function(list){  
		var _this = this;
		_this._trigger('beforeListRender');
		_this.element.find('.item').remove().promise().done(function(){
			list.forEach(function(obj){
				var $new_item = _this._newItem(obj).hide();
				if($new_item.data('id') === _this.options.selectedId){
					$new_item.addClass('selected');
				}
				_this.element.find('.items').append($new_item);
			});
			_this.element.find('.item').fadeIn();
		});
		_this._trigger('afterListRender');
	},
	//Performs a search based on the value on the search input
	_search: function(_this){
		var search_text = _this.element.find('.search input').val().toLowerCase().trim();
		
		clearInterval(_this._variables.timer);
		_this._variables.timer = setTimeout(function(){
			//Go to initial list if already searched and now the input is empty
			if(!search_text && _this.element.find('.dd-breadcrumb .breadcrumb-item').last().text() == _this.options.breadcrumbSearchResults){
				//Unselect if option is enabled
				if(_this.options.unselectOnSearch && _this.options.selectedId){
					_this.options.selectedId = null;
					_this._trigger('onUnselect');
				}
				
				var $bc_start = $('<span class="breadcrumb-item">'+_this.options.breadcrumbStart+'</span>');
				$bc_start.data('id',0);
				_this.element.find('.dd-breadcrumb').html($bc_start);
				_this._renderList(_this.options.list);
			}
			//Show search results
			else if(search_text){
				//Unselect if option is enabled
				if(_this.options.unselectOnSearch && _this.options.selectedId){
					_this.options.selectedId = null;
					_this._trigger('onUnselect');
				}
				
				_this.element.find('.dd-breadcrumb').html('<span class="breadcrumb-item search_title">'+_this.options.breadcrumbSearchResults+'</span>').data('id',0);
				var objs = _this._getObjects(_this.options.list, 'title', search_text, false);
				_this._renderList(objs);
				if(!objs.length){
					_this.element.find('.dd-breadcrumb .search_title').text(_this.options.breadcrumbSearchResults+' - ('+_this.options.noSearchResults+')');
				}
			}
		}, _this._variables.searchDelay);
	},
	//Returns the selected item or null if there is no item selected
	getSelected: function(){
		var selected = this.element.find('.selected').length ? this.element.find('.selected') : null;
		return selected;
	}
});