/*****************************************************************************
 *
 * Copyright (C) 2009 NagVis Project
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/

package modules.gmap.view.controls
{
	import com.google.maps.Map;

	import flash.events.Event;

	import modules.gmap.data.LinksData;
	import modules.gmap.domain.Link;

	import mx.core.UIComponent;
	import mx.events.CollectionEvent;

	public class GMapLinksControl extends UIComponent
	{
		private var _dataProvider : LinksData;
		private var _map : Map;
		private var _lines : Array;

		public function GMapLinksControl()
		{
			super();
		}

		public function get map():Map
		{
			return _map;
		}

		//Already initialized map has to be set here
		//TODO: support uninitialized map
		public function set map(value : Map) : void
		{
			if (_map !== value)
			{
				_map = value;

				reinitLines();

				if (visible)
					showLines();
			}
		}

		public function get dataProvider() : LinksData
		{
			return _dataProvider;
		}

		public function set dataProvider(value : LinksData) : void
		{
			if (_dataProvider !== value)
			{
				_dataProvider = value;
				_dataProvider.addEventListener(CollectionEvent.COLLECTION_CHANGE, onDataProviderChanged);

				reinitLines();
			}
		}

		protected function onDataProviderChanged(event : CollectionEvent) : void
		{
			//TODO: do something
		}

		public override function set visible(value : Boolean) : void
		{
			if (super.visible != value)
			{
				if (value)
					showLines();
				else
					hideLines();

				super.visible = value;
			}
		}

		protected function reinitLines() : void
		{
			if (visible)
				hideLines();

			_lines = [];
			for each (var l : Link in _dataProvider)
				createLine(l);
		}

		protected function showLines() : void
		{
			if (_map)
			{
				for each (var l : LinkLine in _lines)
					_map.addOverlay(l);
			}
		}

		protected function hideLines() : void
		{
			if (_map)
			{
				for each (var l : LinkLine in _lines)
					_map.removeOverlay(l);
			}
		}

		protected function createLine(link:Link) : void
		{
			if (_map)
			{
				var l : LinkLine = new LinkLine(link);
				//m.addEventListener(LocationEvent.SELECTED, redispatchMarkerEvent);
				//m.addEventListener(LocationEvent.ACTIVATE, redispatchMarkerEvent);
				_lines.push(l);

				if (visible)
					_map.addOverlay(l);
			}
		}

		// Marker is not an UI component, so
		// we need to redispatch his events to get them into Mate.
		protected function redispatchMarkerEvent(event : Event) : void
		{
			dispatchEvent(event);
		}
	}
}
