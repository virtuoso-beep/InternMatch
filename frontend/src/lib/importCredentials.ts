import { mutate } from "./api";

export async function downloadImportCredentials(id: string) {
  const { content } = await mutate<{ content: string }>(`/coordinator-imports/${id}/credentials`);
  const url = URL.createObjectURL(new Blob([content], { type: "text/plain;charset=utf-8" }));
  const link = document.createElement("a");
  link.href = url;
  link.download = `student-credentials-${id}.txt`;
  link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
