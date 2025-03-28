/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @file
 * @ingroup FacetedSearchScripts
 * @author: Thomas Schweitzer
 */
console.log("ER: Loading scripts/FacetedSearch/FS_ClusterWidget.js");

if (typeof window.FacetedSearch == "undefined") {
//	Define the FacetedSearch module	
	window.FacetedSearch = { 
			classes : {}
	};
}

/**
 * @class ClusterWidget
 * This is the class for a widget that represents the clusters of a property. 
 * Its target selector is the ID of the property whose values are clustered.
 * 
 */
FacetedSearch.classes.ClusterWidget = AjaxSolr.AbstractWidget.extend({

	/**
	 * This is the click handler for a cluster of values for an attribute.
	 * @param {Object} cluster
	 * 		A description of the cluster with the fields 
	 * 		- from
	 * 		- to
	 * 		- count
	 * 		- facet
	 * 		- facetStatisticField
	 * @returns {Function} Sends a request to Solr if it successfully adds a
	 *   filter query with the given value.
	 */
	clickClusterHandler: function (cluster) {
		var self = this;
		return function () {
			var fsm = FacetedSearch.singleton.FacetedSearchInstance.getAjaxSolrManager();
			fsm.store.addByValue('facet', true);
			var regex = new RegExp(cluster.facetStatisticField+':\\[.*\\]');
			fsm.store.removeByValue('fq', regex);

			var field;
			var ATTRIBUTE_REGEX = /smwh_(.*)_xsdvalue_(.*)/;
			if (cluster.facet.match(ATTRIBUTE_REGEX)) {
				field = FacetedSearch.singleton.FacetedSearchInstance.FACET_FIELDS[1];
			} else {
				field = FacetedSearch.singleton.FacetedSearchInstance.FACET_FIELDS[2];
			}
			fsm.store.addByValue('fq', field + ':' + cluster.facet);

			fsm.store.addByValue('fq', 
				cluster.facetStatisticField+':[' + cluster.from + ' TO ' + cluster.to + ']');
			fsm.doRequest(0);
			return false;
		}
	},

	/**
	 * This function is called when a request to the SOLR manager returns data.
	 * The data contains the facet queries that contains ranges of values of a
	 * semantic attribute and the number of articles whose values are in these
	 * ranges.
	 * This function retrieves the ranges and numbers and passes them to the 
	 * cluster theme that adds html to the attribute facets. 
	 * 
	 */
	afterRequest: function () {
		
		var $ = jQuery;
		var self = this;
		var data = this.manager.response;
		
		// Create strings for the ranges with instance counts
		// e.g. 42 - 52 (5)
		var regex = new RegExp(this.statisticsFieldName+':\\[(\-?\\d*.?\\d*) TO (\-?\\d*.?\\d*)\\]'); 
		var ranges = data.facet_counts.facet_queries;
		var minValue = null;
		var maxValue = null;
		var numRanges = 0;
		// Count the ranges that actually contain articles
		for (var range in ranges) {
			if (ranges[range] > 0) {
				++numRanges;
			}
		}
		
		var target = $(this.target);
		target.empty();
		target.append("<div><span class=\"xfsClusterTitle\"> from: <input style='width: 90px' class='xfsClusterRangepicker xfsClusterRangepickerFrom' type='text'/> to: <input style='width: 90px' class='xfsClusterRangepicker xfsClusterRangepickerTo' type='text'/>"
			+" <a class='xfsClusterRangepicker-apply'>(apply)</a></span></div>")
		for (var range in ranges) {
			var matches = range.match(regex);
			if (matches) {
				var from = matches[1];
				var displayFrom = this.clusterer.formatBoundary(from);
				if (!minValue) {
					minValue = displayFrom;
				}
				var to = matches[2];
				var displayTo = this.clusterer.formatBoundary(to);
				maxValue = displayTo;
				var count = ranges[range];
				if (count > 0) {
					// Create the HTML for the cluster
					target.append(AjaxSolr.theme('cluster',
						displayFrom, displayTo, count,
						self.clickClusterHandler({
							from: from,
							to: to,
							count: count,
							facetStatisticField: this.statisticsFieldName,
							facet: this.facetName
						}),
						false, false, numRanges === 1
						));
				}
			}
		}

		if (self.getType(this.facetName) === 'date') {
			target.find('.xfsClusterRangepicker').datepicker({dateFormat: 'yy/mm/dd',});
		}
		target.find('.xfsClusterRangepicker-apply').click((e) => {
			let target = $(e.target);
			let datepickerFrom = target.closest('.xfsClusterTitle').find('.xfsClusterRangepickerFrom').val();
			let datepickerTo = target.closest('.xfsClusterTitle').find('.xfsClusterRangepickerTo').val();
			let from, to;
			if (self.getType(this.facetName) === 'date') {
				from = datepickerFrom.replaceAll('/','')+"000000";
				to = datepickerTo.replaceAll('/','')+"235959";
			} else {
				from = datepickerFrom;
				to = datepickerTo;
			}
			self.clickClusterHandler({
				from: from,
				to: to,
				count: 0,
				facetStatisticField: this.statisticsFieldName,
				facet: this.facetName
			})();
		});
		// Check if the range is already restricted
		var rangeRestricted = false;
		var fq = this.manager.store.values('fq');
		for (var i = 0, l = fq.length; i < l; i++) {
			if (fq[i].indexOf(this.statisticsFieldName) == 0) {
				rangeRestricted = true;
				break;
			}
		}
				
		// get total num of facet matches for this property
		var smwh_attributes = data.facet_counts.facet_fields.smwh_attributes;
		var totalHitCount = smwh_attributes[self.facetName];
		
		// Show the cluster title
		target.prepend(AjaxSolr.theme('cluster', minValue, maxValue, 
								   totalHitCount, 
								   this.clickRemoveRangeHandler(this.statisticsFieldName),
								   true, rangeRestricted));
		
		
		// Remove the statistic parameters
		this.manager.store.remove('facet.query');
		
	},
	
	/**
	 * Removes a range restriction for a facet.
	 * @param {string} facet
	 * 		Name of the facet
	 */
	clickRemoveRangeHandler: function (facet) {
		var self = this;
		return function() {
			var fsm = FacetedSearch.singleton.FacetedSearchInstance.getAjaxSolrManager();
			var fq = fsm.store.values('fq');
			for (var i = 0, l = fq.length; i < l; i++) {
				if (fq[i].indexOf(facet) == 0) {
					fsm.store.removeByValue('fq', fq[i]);
					break;
				}
			}
			fsm.doRequest(0);
			return false;
		};
	},

	getType: function(facetName) {
		var ATTRIBUTE_REGEX = /smwh_(.*)_xsdvalue_(.*)/;
		var RELATION_REGEX  = /smwh_(.*)_(.*)/;

		var nameType = facetName.match(ATTRIBUTE_REGEX);
		if (!nameType) {
			// maybe a relation facet
			nameType = facetName.match(RELATION_REGEX);
			if (!nameType) {
				return null;
			}
		}
		var type = nameType[2];
		switch (type) {
			case 'd':
			case 'i':
				// numeric
				return 'numeric'
			case 'dt':
				// date
				return 'date'
			case 'b':
				return 'boolean'
			default:
				return 'string';
		}
	}
});

/**
 * This function is called when the details of a property facet are to be shown.
 * @param {string} facet
 * 		Name of the facet
 * 
 */
FacetedSearch.classes.ClusterWidget.showPropertyDetailsHandler = function(facet) {
	var clusterer = FacetedSearch.factories.FacetClustererFactory(facet);
	clusterer.retrieveClusters();
};

