console.log('main.js loaded');

// let's set some vars
window.loop_time = 5000;

$(document).ready(function(){

	hbg.activateNav();
	hbg.registerClicks();
	hbg.registerJobs();
	hbg.modifyContains();

	$(document).on('input','#search',(e)=>{ hbg.execSearch(e) });
});



const hbg = (() => {



	const activateNav = (navActive) => {

		let activeNav = (localStorage.getItem('activeNav')) ? localStorage.getItem('activeNav') : $('label[id^=nav-]')[0].id;
			activeNav = (navActive) ? navActive : activeNav; // if parameter given (onclick)

		localStorage.setItem('activeNav', activeNav);
		$('#'+activeNav).addClass('active');

		console.log('Switching to: '+activeNav);
		hbg.getContents(activeNav);

	};	



    const capFirstLetter = (string) => {

        const str = string.charAt(0).toUpperCase() + string.slice(1);
        return str;
    };




    const checkMulti = (ele) => {

    	const checked = ele.target.checked;
    	$('tr:not(.hideme) .checkme').prop('checked', checked);

        return;
    };



    const checkSingle = (ele) => {

    	// what the fuck
    	const el = $('#checkall');
    		  cV = $('tr:not(.hideme) .checkme').length,
    		  cC = $('.checkme:checked').length,
    		  cA = el.prop('checked'),
    		  cS = (ele.target) ? ele.target.checked : ele.checked;

    	if(!cS && !cC)	{ 	el.prop('checked', false);		
    						el.prop('indeterminate', false);} else
    	if(cV === cC)	{	el.prop('checked', true);		
							el.prop('indeterminate', false);} else
    	if(cA)			{	el.prop('indeterminate', cA);	}

        return;
    };



    const execSearch = (ele) => {

    	const val = ele.target.value;

    	$('.scrollme tbody tr td:not(:contains("'+val+'"))').parent().addClass('hideme');
		$('.scrollme tbody tr td:contains("'+val+'")').parent().removeClass('hideme');

		// tell checkMulti the dataset changed :>
		hbg.checkSingle($('.checkme:not(.hidden):eq(0)')[0]);

        return;
    };



    const fetchData = async(fn, args) => {

    	let result;

	    try {
	    	result = await $.ajax({
		        url: '/hbgui/php/functions.php',
		        type: 'POST',
		        data: { fn: fn, args: args }
		    });

	    	return result;
		} catch (err) {
	    	console.error(err);
	    }
    };



	const getContents = async(navId, params) => {

		navId = hbg.capFirstLetter(navId.replace('nav-', ''));

		// Remove content and add spinner
		$('#content').remove();
		$('#wrapper').append(`
			<div class="container-fluid h-100"  id="content">
				<div class="d-flex justify-content-center">
					<div class="spinner-border text-primary" role="status"></div>
				</div>
			</div>
		`);

		const data = await hbg.fetchData('fetch'+navId, params);

		// Remove spinner and add content
		$('#content').remove()
		$('#wrapper').append(data);

		$('form input[data-role="tagsinput"]').tagsinput('refresh');
	};



	const getJobStatus = async() => {

		const 	stat = $('#jobstatus').length,
				data = await hbg.fetchData('checkForRunningJob', stat),
				anc  = $('#menu .row .col-3:first-child'),
				job  = document.getElementById('jobstatus');

		if(stat && data)  job.textContent.replace(data);
		if(stat && !data) anc.html('');
		if(data && !stat) anc.html(data);

	};



	const getMachineInfo = async() => {

		const data = await hbg.fetchData('fetchMachineInfo', '');

		for(let i=0;i<data.length;i++){

			const ele = $('#menu .row div:nth-child(3) button:eq('+i+') .badge span', document)[0];
			ele.innerHTML = data[i] || 0;

		}

	};



	const modifyContains = async() => {

		jQuery.expr[':'].contains = function(a, i, m) {
			return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
		};

	};



	const registerClicks = () => {

		$(document).on('click', function(e){

			const clicked_id 	= $(e.target)[0].id,
				  clicked_class = $(e.target).eq(0).attr('class');

			if(clicked_class === 'checkme') hbg.checkSingle(e); // special handling for checkboxes
			if(!clicked_id) return;

			switch (true) {
				case /nav-.*/.test(clicked_id):
					hbg.activateNav(clicked_id);
				break;
				case /saveme/.test(clicked_id):
					hbg.saveSettings();
				break;
				case /killme/.test(clicked_id):
					hbg.resetSettings();
				break;
				case /rescanfiles/.test(clicked_id):
					hbg.getContents('files', true);
				break;
				case /checkall/.test(clicked_id):
					hbg.checkMulti(e);
				break;
				default:
					// ignore
				break;
			}

		});
		
	};



	const registerJobs = async() => {

		const data = await hbg.fetchData('fetchSettingsData', 'gui_auto_refresh,val1');
		window.loop_time = parseInt(data[0])*1000;
		setInterval(hbg.runJobs, window.loop_time);

	};



	const runJobs = (interval) => {

		hbg.getMachineInfo();
		hbg.getJobStatus();

	};



	const resetSettings = async() => {

		const data = await hbg.fetchData('resetSettings', '');
		hbg.getContents('Settings');

	};



	const saveSettings = async() => {

		const inputs = $('form input[id^=settings]');
		let val = '';
		let arr = [];

		for(let i=0; i<inputs.length; i++){

			const type = $(inputs[i]).data('type');

			switch (type) {
			  case 'tags':
			  	val = $(inputs[i]).tagsinput('items').toString();
			  	break;
			  case 'number':
			  	val = $(inputs[i]).val();
			  	break;
			  case 'text':
			  	val = $(inputs[i]).val();
			  	break;
			  case 'switch':
			  	val = +$(inputs[i]).is(':checked');
			  	break;
			  default:
			  	console.log('Error: Type not defined');
			}

			arr.push({
				id: $(inputs[i]).data('dbid'),
				val: val
			});
		}

		const data = await hbg.fetchData('updateSettings', arr);
		hbg.getContents('Settings');

	};



	return {
		activateNav,
		capFirstLetter,
		checkMulti,
		checkSingle,
		execSearch,
		fetchData,
		getContents,
		getJobStatus,
		getMachineInfo,
		modifyContains,
		registerClicks,
		registerJobs,
		resetSettings,
		runJobs,
		saveSettings
	};

})();