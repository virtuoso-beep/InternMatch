import { useEffect, useRef, useState } from "react";
import type { Map as LeafletMap } from "leaflet";
import "leaflet/dist/leaflet.css";
import { api } from "@/lib/api";
import { Card, CardTitle, cx } from "./ui";

type Point = {name:string;latitude:number;longitude:number};
type Host = {id:number;name:string;city:string;latitude:number|null;longitude:number|null;distance_km:number|null};
type Data = {student:Point|null;hosts:Host[]};

export function AccessibilityMap({note}:{note?:string}){
  const [data,setData]=useState<Data|null>(null),[selected,setSelected]=useState<number|null>(null),[error,setError]=useState(""),[tileError,setTileError]=useState(false);
  const container=useRef<HTMLDivElement>(null),map=useRef<LeafletMap|null>(null);
  const active=data?.hosts.find(h=>h.id===selected);
  useEffect(()=>{let mounted=true;api<Data>("/map").then(d=>{if(mounted){setData(d);setSelected(d.hosts[0]?.id??null);}}).catch((e:Error)=>{if(mounted)setError(e.message);});return()=>{mounted=false;};},[]);
  useEffect(()=>{
    if(!data||!container.current)return;
    let mounted=true;
    void import("leaflet").then(L=>{
      if(!mounted||!container.current)return;
      const instance=L.map(container.current).setView([0,0],2);map.current=instance;
      L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).on('tileerror',()=>{if(mounted)setTileError(true);}).addTo(instance);
      const points:[number,number][]=[];
      const add=(point:Point,student:boolean,id?:number)=>{
        const coords:[number,number]=[point.latitude,point.longitude];points.push(coords);
        const label=document.createElement('span');label.textContent=`${student?'Student':'Host'}: ${point.name}`;
        const marker=L.marker(coords,{title:label.textContent,icon:L.divIcon({className:'',html:`<span style="display:block;width:18px;height:18px;border:3px solid white;border-radius:50%;background:${student?'#172554':'#701724'};box-shadow:0 1px 4px #555"></span>`,iconSize:[18,18],iconAnchor:[9,9]})}).addTo(instance).bindPopup(label);
        if(id!==undefined)marker.on('click',()=>setSelected(id));
      };
      if(data.student)add(data.student,true);
      data.hosts.forEach(h=>{if(h.latitude!==null&&h.longitude!==null)add({...h,latitude:h.latitude,longitude:h.longitude},false,h.id);});
      if(points.length)instance.fitBounds(L.latLngBounds(points),{padding:[35,35],maxZoom:14});
    }).catch((e:Error)=>{if(mounted)setError(e.message);});
    return()=>{mounted=false;map.current?.remove();map.current=null;};
  },[data]);
  function choose(host:Host){setSelected(host.id);if(host.latitude!==null&&host.longitude!==null)map.current?.setView([host.latitude,host.longitude],14);}
  return <div className="grid gap-5 lg:grid-cols-[1.6fr_1fr]">
    <Card className="p-0"><div className="border-b border-border px-5 py-4"><h2 className="text-base font-semibold">Geographic distribution of host establishments</h2>
      <p className="mt-1 text-sm text-muted-foreground">{note??"Saved student and host coordinates. Distances are straight-line kilometers, not travel time."}</p>
      {error&&<p role="alert">{error}</p>}{!data&&!error&&<p role="status">Loading saved locations…</p>}
      {tileError&&<p role="status" className="mt-2 text-xs">Base-map tiles are unavailable. Saved location markers and distances remain available.</p>}
    </div><div ref={container} aria-label="InternMatch location map" className="relative z-0 h-[420px] overflow-hidden rounded-b-lg bg-[oklch(0.94_0.02_200)]" /></Card>
    <div className="space-y-4"><Card><CardTitle>{active?.name??"Host location"}</CardTitle>
      {active?<div className="mt-4 space-y-2 text-sm"><Line label="Location" value={active.city}/><Line label="Student-to-host distance" value={active.distance_km===null?"Student or host coordinates unavailable":`${active.distance_km.toFixed(2)} km straight-line`}/><Line label="Coordinates" value={active.latitude===null?'Not recorded':`${active.latitude}, ${active.longitude}`}/></div>:<p className="text-sm">No host records are available within your access scope.</p>}
      {data?.student?<p className="mt-4 text-xs">Student marker: {data.student.name} · {data.student.latitude}, {data.student.longitude}</p>:<p className="mt-4 text-xs">No student location selected or recorded.</p>}
    </Card><Card><CardTitle>All establishments</CardTitle><div className="space-y-1">{data?.hosts.map(host=><button key={host.id} type="button" onClick={()=>choose(host)} className={cx("flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm",selected===host.id?"bg-brand-soft font-semibold text-brand":"hover:bg-muted")}><span>{host.name}</span><span className="text-xs text-muted-foreground">{host.distance_km===null?'—':`${host.distance_km.toFixed(2)} km`}</span></button>)}</div></Card></div>
  </div>;
}

function Line({label,value}:{label:string;value:string}){return <div className="flex justify-between gap-3 border-b border-border/60 pb-2 last:border-0"><span className="text-muted-foreground">{label}</span><span className="font-medium text-foreground">{value}</span></div>;}
