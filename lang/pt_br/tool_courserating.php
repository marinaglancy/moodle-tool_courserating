<?php 
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     tool_courserating
 * @category    string
 * @copyright   2022 Marina Glancy <marina.glancy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addrating'] = 'Deixe uma avaliação';
$string['barwithrating'] = '{$a->rating} estrela representa {$a->percent} da avaliação';
$string['cannotrate'] = 'Você não tem permissão para deixar uma avaliação deste curso';
$string['cannotview'] = 'Você não tem permissão para ver as avaliações deste curso';
$string['cfielddescription'] = 'Não edite, o conteúdo será populado automaticamente cada vez que alguém deixar uma avaliação deste curso.';
$string['colorrating'] = 'Cor da avaliação';
$string['colorratingconfig'] = 'Isto é geralmente um pouco mais escuro que a cor da estrela para melhor efeito visual';
$string['colorstar'] = 'Cor das estrelas';
$string['courserating:delete'] = 'Deleta avaliações de curso e reviews, veja reviews marcadas';
$string['courserating:rate'] = 'Deixe uma avaliação para a sala';
$string['courserating:reports'] = 'Veja o relatório de avaliações de curso';
$string['coursereviews'] = 'Avaliações dos cursos';
$string['datasource_courseratings'] = "Avaliações de curso";
$string['deleterating'] = 'Excluir permanentemente';
$string['deletereason'] = 'Motivo para exclusão';
$string['displayempty'] = 'Não mostrar avaliações com estrelas cinzas';
$string['displayemptyconfig'] = 'Para cursos que avaliação está ativada mas não tem avaliações, mostra estrelas cinzas. Se não selecionado, estes cursos não irão mostrar nenhum avaliação';
$string['editrating'] = 'Edite sua avaliação';
$string['entity_rating'] = "Avaliação de curso por usuário";
$string['entity_summary'] = "Sumário da avaliação de curso";
$string['event:flag_created'] = 'Avaliação de curso marcada';
$string['event:flag_deleted'] = 'Marcação na avaliação de curso removida';
$string['event:rating_created'] = 'Avaliação de curso criada';
$string['event:rating_deleted'] = 'Avaliação de curso excluída';
$string['event:rating_updated'] = 'Avaliação de curso atualizada';
$string['flagrating'] = 'Marcar';
$string['parentcss'] = 'Seletor CSS para elemento pai';
$string['parentcssconfig'] = 'Avaliação de curso será exibida na pagina do curso como ultimo elemento filho do elemento da DOM selecionado. Talvez seja necessário sobrescrever caso use um tema personalizado e você quer especificar um elemento pai diferente. Se vazio, o valor padrão sera usado. Para Moodle 4.0 o padrão é "#page-header", para Moodle 3.11 o padrão é "#page-header .card-body, #page-header #course-header, #page-header".';
$string['percourseoverride'] = 'Sobrescritas por curso';
$string['percourseoverrideconfig'] = 'Se habilitado, um campo personalizado que irá permitir setar individualmente quais cursos podem ser avaliados será criado. O valor da configuração "Quando os cursos podem ser avaliados" será tratado como padrão';
$string['pluginname'] = 'Avaliações de curso';
$string['privacy:metadata:tool_courserating:reason'] = 'Motivo';
$string['privacy:metadata:tool_courserating:reasoncode'] = 'Código do motivo';
$string['privacy:metadata:tool_courserating:timecreated'] = 'Data da criação';
$string['privacy:metadata:tool_courserating:timemodified'] = 'Data da modificação';
$string['privacy:metadata:tool_courserating_flag'] = 'Avaliações marcadas';
$string['privacy:metadata:tool_courserating_flag:id'] = 'Id';
$string['privacy:metadata:tool_courserating_flag:ratingid'] = 'Id da avaliação';
$string['privacy:metadata:tool_courserating_flag:userid'] = 'Id do usuário';
$string['privacy:metadata:tool_courserating_rating'] = 'Avaliações de curso';
$string['privacy:metadata:tool_courserating_rating:cohortid'] = 'Id do curso';
$string['privacy:metadata:tool_courserating_rating:hasreview'] = 'Tem avaliação';
$string['privacy:metadata:tool_courserating_rating:id'] = 'Id';
$string['privacy:metadata:tool_courserating_rating:rating'] = 'Avaliação';
$string['privacy:metadata:tool_courserating_rating:review'] = 'Review';
$string['privacy:metadata:tool_courserating_rating:timecreated'] = 'Data da criação';
$string['privacy:metadata:tool_courserating_rating:timemodified'] = 'Data da modificação';
$string['privacy:metadata:tool_courserating_rating:userid'] = 'Usuário';
$string['ratebyanybody'] = 'Alunos podem avaliar o curso a qualquer momento';
$string['ratebycompleted'] = 'Alunos só podem avaliar apos completar o curso';
$string['ratebydefault'] = 'Valor padrão é: "{$a}"';
$string['ratebynoone'] = 'Avaliações de curso está desativadas';
$string['ratedcategory'] = 'Categoria onde é permitido avaliar cursos';
$string['rating'] = 'Avaliação';
$string['rating_actions'] = "Ações";
$string['rating_hasreview'] = "Tem um review";
$string['rating_nofflags'] = "Numero de marcações";
$string['rating_rating'] = "Avaliação de curso";
$string['rating_review'] = "Review";
$string['rating_timecreated'] = "Data da criação";
$string['rating_timemodified'] = "Data da modificação";
$string['ratingasstars'] = 'Avaliação do curso como estrela';
$string['ratingdeleted'] = 'Avaliação excluída';
$string['ratinglabel'] = 'Avaliação de curso';
$string['ratingmode'] = 'Quando cursos podem ser avaliados';
$string['ratingmodeconfig'] = 'Além disso, a capacidade de classificar os cursos é verificada';
$string['reindextask'] = 'Re-indexar avaliações de curso';
$string['review'] = 'Avaliação (opcional)';
$string['revokeratingflag'] = 'Revogue';
$string['settingsdescription'] = 'Modificar algumas opções podem ser necessário reindexar todos os cursos e avaliações. Isto ira acontecer automaticamente na próxima vez que o cron rodar.';
$string['showallratings'] = 'Mostrar todos';
$string['showmorereviews'] = 'Mostrar mais';
$string['summary_avgrating'] = "Avaliação de curso";
$string['summary_cnt01'] = "Proporção da avaliação de 1-estrela";
$string['summary_cnt02'] = "Proporção da avaliação de 2-estrela";
$string['summary_cnt03'] = "Proporção da avaliação de 3-estrela";
$string['summary_cnt04'] = "Proporção da avaliação de 4-estrela";
$string['summary_cnt05'] = "Proporção da avaliação de 5-estrela";
$string['summary_cntall'] = "Numero de avaliações";
$string['summary_cntreviews'] = "Numero de reviews";
$string['summary_ratingmode'] = "Modo de avaliação de curso";
$string['summary_sumrating'] = "Total de todas avaliações";
$string['usehtml'] = 'Use o editor rico de texto para reviews';
$string['usehtmlconfig'] = 'Permitir que alunos usem o editor rico de texto para reviews, incluir link e arquivos.';
$string['usersflagged'] = '{$a} usuário(s) marcaram esse review como inapropriado/ofensivo.';
$string['viewallreviews'] = 'Vija todos os reviews';
$string['youflagged'] = 'Você marcou esse review como inapropriado/ofensivo.';
