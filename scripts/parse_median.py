#!/usr/bin/env python3
import sys
import re

def parse_file(path):
    try:
        with open(path, "r", encoding="utf-8") as f:
            text = f.read()
    except Exception as e:
        return {"rps": 0.0, "p50": 0.0, "p95": 0.0, "p99": 0.0, "err_pct": 100.0}

    rps_m = re.search(r"Requests/sec:\s+([\d.]+)", text)
    rps = float(rps_m.group(1)) if rps_m else 0.0

    p50_m = re.search(r"50%\s+in\s+([\d.]+)\s+secs", text)
    p50 = float(p50_m.group(1)) * 1000 if p50_m else 0.0

    p95_m = re.search(r"95%\s+in\s+([\d.]+)\s+secs", text)
    p95 = float(p95_m.group(1)) * 1000 if p95_m else 0.0

    p99_m = re.search(r"99%\s+in\s+([\d.]+)\s+secs", text)
    p99 = float(p99_m.group(1)) * 1000 if p99_m else 0.0

    total_resp = 0
    err_resp = 0

    if "Status code distribution:" in text:
        section = text.split("Status code distribution:")[1]
        if "Error distribution:" in section:
            sc_part, err_part = section.split("Error distribution:")
        else:
            sc_part = section
            err_part = ""
        for code, count in re.findall(r"\[(\d+)\]\s+(\d+)\s+responses", sc_part):
            c = int(count)
            total_resp += c
            if not code.startswith("2"):
                err_resp += c
        for count in re.findall(r"^\s*\[(\d+)\]\s+", err_part, re.MULTILINE):
            c = int(count)
            total_resp += c
            err_resp += c
    elif "Error distribution:" in text:
        err_part = text.split("Error distribution:")[1]
        for count in re.findall(r"^\s*\[(\d+)\]\s+", err_part, re.MULTILINE):
            c = int(count)
            total_resp += c
            err_resp += c

    err_pct = (err_resp / total_resp * 100.0) if total_resp > 0 else 0.0

    return {
        "rps": rps,
        "p50": p50,
        "p95": p95,
        "p99": p99,
        "err_pct": err_pct
    }

def main():
    if len(sys.argv) < 5:
        print("Uso: parse_median.py <Target> <Endpoint> <Concurrency> <file1> [file2...]", file=sys.stderr)
        sys.exit(1)

    target = sys.argv[1]
    endpoint = sys.argv[2]
    concurrency = sys.argv[3]
    files = sys.argv[4:]

    parsed_runs = [parse_file(f) for f in files]
    # Ordenamos por RPS y seleccionamos la mediana
    sorted_runs = sorted(parsed_runs, key=lambda x: x["rps"])
    median_idx = len(sorted_runs) // 2
    median = sorted_runs[median_idx]

    # Imprimimos linea CSV: Arquitectura,Endpoint,Concurrencia,RPS,p50_ms,p95_ms,p99_ms,Errores_pct
    csv_line = f"{target},{endpoint},{concurrency},{median['rps']:.2f},{median['p50']:.2f},{median['p95']:.2f},{median['p99']:.2f},{median['err_pct']:.2f}"
    print(csv_line)

if __name__ == "__main__":
    main()
