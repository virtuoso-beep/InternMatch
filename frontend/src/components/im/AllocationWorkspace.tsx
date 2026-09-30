import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader, Pill } from "./ui";

type Item = { id:number; student_name:string; student_enrollment_id:number; opportunity_id:number|null; opportunity_title:string|null; host_name:string|null; similarity_score:number|null; distance_km:number|null; reason:string; review_status:string; review_reason:string|null };
type Data = {
  cohorts:{id:number;program:{code:string};academic_term:{name:string}}[];
  students:{id:number;name:string;program_term_id:number;academic_eligibility_confirmed:boolean|null;reasons:string[]}[];
  proposals:{id:number;program_term_id:number;status:string;items:Item[]}[];
  opportunities:{id:number;title:string;host_establishment_id:number}[];
  supervisors:{id:number;name:string;host_establishments:{id:number}[]}[];
};
const inputClass="mt-1 block w-full rounded-lg border border-input bg-background p-2 text-sm";

export function AllocationWorkspace(){
  const [data,setData]=useState<Data|null>(null),[error,setError]=useState(""),[busy,setBusy]=useState(false),[cohort,setCohort]=useState("");
  const [review,setReview]=useState<Item|null>(null),[opportunity,setOpportunity]=useState(""),[supervisor,setSupervisor]=useState(""),[reason,setReason]=useState(""),[start,setStart]=useState(""),[end,setEnd]=useState("");
  const [assessment,setAssessment]=useState<Data["students"][number]|null>(null),[eligible,setEligible]=useState(false);
  async function load(){const next=await api<Data>("/allocation");setData(next);setCohort(current=>current||String(next.cohorts[0]?.id??""));}
  useEffect(()=>{void load().catch((e:Error)=>setError(e.message));},[]);
  async function perform(action:()=>Promise<unknown>){setBusy(true);setError("");try{await action();await load();setReview(null);setAssessment(null);}catch(e){setError(e instanceof Error?e.message:"Unable to save decision.");}finally{setBusy(false);}}
  const proposal=data?.proposals.find(p=>String(p.program_term_id)===cohort);
  const selected=data?.opportunities.find(o=>String(o.id)===opportunity);
  return <>
    <PageHeader title="Pending approvals" subtitle="Review cohort proposals and confirm each final placement." />
    {error&&<p role="alert" className="text-destructive">{error}</p>}
    {!data&&!error&&<p role="status">Loading allocation records…</p>}
    <div className="flex flex-wrap items-end gap-3">
      <label className="text-sm font-semibold">Program cohort<select aria-label="Program cohort" className={inputClass} value={cohort} onChange={e=>setCohort(e.target.value)}>{data?.cohorts.map(t=><option key={t.id} value={t.id}>{t.program.code} · {t.academic_term.name}</option>)}</select></label>
      <Button disabled={busy||!cohort} onClick={()=>void perform(()=>mutate(`/program-terms/${cohort}/allocation`,{}))}>Generate allocation proposal</Button>
    </div>
    <div className="grid gap-5 lg:grid-cols-2">
      <Card><CardTitle>Placement approvals</CardTitle>
        {!proposal?<p>No proposal has been generated for this cohort.</p>:<>
          <p className="mb-3 text-sm text-muted-foreground">Proposal {proposal.id} · {proposal.status}. Proposed slots are not final reservations.</p>
          <div className="space-y-3">{proposal.items.map(item=><div key={item.id} className="rounded-md border border-border p-4">
            <div className="flex items-center justify-between gap-3"><p className="font-semibold">{item.student_name}</p><Pill>{item.review_status}</Pill></div>
            <p className="mt-1 text-sm text-muted-foreground">{item.host_name??"Unassigned"}{item.opportunity_title?` · ${item.opportunity_title}`:""}</p>
            {item.similarity_score!==null&&<p className="text-sm">{(Number(item.similarity_score)*100).toFixed(1)}% similarity · {item.distance_km===null?"Distance unavailable":`${Number(item.distance_km).toFixed(2)} km straight-line`}</p>}
            <p className="mt-2 text-sm">{item.review_reason??item.reason}</p>
            {item.review_status==='pending'&&<div className="mt-3 flex gap-2"><Button disabled={busy} onClick={()=>{setReview(item);setOpportunity(String(item.opportunity_id??""));setSupervisor("");setReason("");setStart("");setEnd("");}}>Review placement</Button></div>}
          </div>)}</div>
        </>}
      </Card>
      <Card><CardTitle>Placement readiness</CardTitle>
        <div className="space-y-3">{data?.students.filter(s=>String(s.program_term_id)===cohort).map(student=><div key={student.id} className="rounded-md border border-border p-4">
          <p className="font-medium">{student.name}</p><p className="mt-2 text-sm">{student.reasons.length?student.reasons.join(' '):'Ready for allocation'}</p>
          <div className="mt-3"><Button variant="outline" disabled={busy} onClick={()=>{setAssessment(student);setEligible(student.academic_eligibility_confirmed===true);setReason("");}}>Academic review</Button></div>
        </div>)}</div>
        <p className="mt-3 text-xs text-muted-foreground">BSIT requires fourth-year enrollment and no failing major subjects. Confirm against department records. Requirement documents and event attendance are reviewed in Requirements.</p>
      </Card>
    </div>
    {(review||assessment)&&<div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 px-4">
      <div role="dialog" aria-modal="true" aria-label={review?'Review placement':'Academic eligibility'} className="max-h-[90vh] w-full max-w-md overflow-auto rounded-lg border border-border bg-card p-5 shadow-xl">
        <h2 className="text-lg font-semibold">{review?.student_name??assessment?.name}</h2>
        {assessment?<label className="mt-3 flex gap-2 text-sm"><input type="checkbox" checked={eligible} onChange={e=>setEligible(e.target.checked)}/>Department academic eligibility confirmed</label>:<>
          <label className="mt-3 block text-sm">Opportunity<select aria-label="Opportunity" className={inputClass} value={opportunity} onChange={e=>{setOpportunity(e.target.value);setSupervisor("");}}><option value="">Select opportunity</option>{data?.opportunities.map(o=><option key={o.id} value={o.id}>{o.title} (#{o.id})</option>)}</select></label>
          <label className="mt-3 block text-sm">Supervisor<select aria-label="Supervisor" className={inputClass} value={supervisor} onChange={e=>setSupervisor(e.target.value)}><option value="">Select supervisor</option>{data?.supervisors.filter(s=>s.host_establishments.some(h=>h.id===selected?.host_establishment_id)).map(s=><option key={s.id} value={s.id}>{s.name}</option>)}</select></label>
          <label className="mt-3 block text-sm">Start date<input className={inputClass} type="date" value={start} onChange={e=>setStart(e.target.value)}/></label>
          <label className="mt-3 block text-sm">End date<input className={inputClass} type="date" value={end} onChange={e=>setEnd(e.target.value)}/></label>
        </>}
        <label className="mt-3 block text-sm">Decision reason<textarea className={inputClass} value={reason} onChange={e=>setReason(e.target.value)} maxLength={2000}/></label>
        {error&&<p role="alert" className="mt-2 text-sm text-destructive">{error}</p>}
        <div className="mt-5 flex flex-wrap gap-2">
          {assessment?<Button disabled={busy||reason.trim().length<5} onClick={()=>void perform(()=>mutate(`/enrollments/${assessment.id}/academic-eligibility`,{eligible,reason}))}>Save academic review</Button>:<>
            <Button disabled={busy||!opportunity||!supervisor||!start||!end||reason.trim().length<5} onClick={()=>void perform(()=>mutate(`/allocation-items/${review!.id}/decision`,{decision:'approve',opportunity_id:Number(opportunity),supervisor_id:Number(supervisor),starts_on:start,ends_on:end,reason}))}>Approve placement</Button>
            <Button variant="outline" disabled={busy||reason.trim().length<5} onClick={()=>void perform(()=>mutate(`/allocation-items/${review!.id}/decision`,{decision:'reject',reason}))}>Reject proposal</Button>
          </>}
          <Button variant="outline" disabled={busy} onClick={()=>{setReview(null);setAssessment(null);setError("");}}>Cancel</Button>
        </div>
      </div>
    </div>}
  </>;
}

