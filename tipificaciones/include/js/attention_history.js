function addAtention(dom_container, client_name, contact_reason, contact_detail, operator, contact_date, is_new_atention, problem_solved, company_img, contact_img){
	var problem_solved_message = problem_solved == 1 ? 'Problema solucionado' : 'Problema no solucionado';
	var problem_solved_class = problem_solved == 1 ? 'problem_solved' : 'problem_not_solved';
	var new_atention_class = is_new_atention ? "recent_message" : "";
	dom_container.append('<div class="atencion row">'+
							'<div class="col-lg-1">'+
								'<img class="avatar" src="include/img/avatarDefault.png">'+
							'</div>'+
							'<div class="message col-lg-6 '+new_atention_class+'">'+
								'<div class="client_detail">'+
									client_name+' ('+contact_reason+')'+
								'</div>'+
								'<div class="img_container">'+
									'<img class="small_img" src="include/img/marcas/'+company_img+'" >'+
									'<img class="small_img" src="include/img/medios/'+contact_img+'" >'+
								'</div>'+
								'<p class="message_content">'+contact_detail.replace(/</g,'&lt').replace(/>/g,'&gt').replace(/(?:\r\n|\r|\n)/g, '<br />')+'</p>'+
								'<p class="problem_solved_message '+problem_solved_class+'">'+problem_solved_message+'</p>'+
								'<p class="message_info">'+operator+' - '+contact_date+'</p>'+
							'</div>'+
							'<div class="end_message"></div>'+
						'</div>');
	
}