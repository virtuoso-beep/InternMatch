<?php
namespace App\Http\Controllers;
use App\Enums\Role;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class RecommendationBatchReportController extends Controller {
 public function index(Request $request) {
  abort_unless($request->user()->role === Role::Coordinator,403);
  $rows=DB::table('recommendation_batch_reports')->whereIn('program_id',$request->user()->programs()->select('programs.id'))->orderByDesc('id')->paginate(20);
  $rows->through(function($row){$row->results=json_decode($row->results,true);return $row;});
  return response()->json($rows,headers:['Cache-Control'=>'no-store']);
 }
 public function store(Request $request) {
  abort_unless($request->user()->role === Role::Coordinator,403);
  $data=$request->validate(['program'=>'required|string','results'=>'required|array|min:1|max:1000','results.*.enrollment_id'=>'required|integer|distinct','results.*.status'=>'required|in:Updated,Unchanged,Skipped,Failed','results.*.reason'=>'required|string|max:2000','results.*.before_id'=>'nullable|integer','results.*.after_id'=>'nullable|integer']);
  $program=$request->user()->programs()->where('code',$data['program'])->firstOrFail();
  $results=[];
  foreach($data['results'] as $item){
   $enrollment=StudentAccess::enrollments($request->user())->whereHas('programTerm',fn($q)=>$q->where('program_id',$program->id))->findOrFail($item['enrollment_id']);
   $describe=function($id) use ($enrollment) {
    if (!$id) return null;
    $match=\App\Models\Recommendation::where('student_enrollment_id',$enrollment->id)->findOrFail($id);
    return ['host'=>$match->snapshot['host_name']??null,'similarity'=>$match->similarity_score,'distance'=>$match->distance_km];
   };
   $results[]=['previous'=>$describe($item['before_id']??null),'current'=>$describe($item['after_id']??null),'enrollment_id' =>$enrollment->id,'student'=>$enrollment->student->user?->name ?? 'Archived student account','status'=>$item['status'],'reason'=>$item['reason']];
  }
  $id=DB::table('recommendation_batch_reports')->insertGetId(['user_id'=>$request->user()->id,'program_id'=>$program->id,'results'=>json_encode($results),'created_at'=>now()]);
  return response()->json(['id'=>$id],201);
 }
}
